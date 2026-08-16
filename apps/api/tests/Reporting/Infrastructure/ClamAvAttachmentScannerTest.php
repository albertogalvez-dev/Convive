<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentScanVerdict;
use App\Reporting\Infrastructure\ClamAvAttachmentScanner;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Exercises the INSTREAM exchange over a connected socket pair standing in for
 * the daemon, so the protocol and every failure path are verified without
 * provisioning a scanner or shipping signatures into the test environment.
 */
final class ClamAvAttachmentScannerTest extends TestCase
{
    /** @var resource|null */
    private $daemonSide = null;

    protected function tearDown(): void
    {
        if (is_resource($this->daemonSide)) {
            fclose($this->daemonSide);
        }

        parent::tearDown();
    }

    public function testAnEmptyDsnMeansNoScannerAndNeverReportsClean(): void
    {
        $scanner = new ClamAvAttachmentScanner('', new NullLogger());

        self::assertSame(AttachmentScanVerdict::Unavailable, $scanner->scan($this->content('anything')));
    }

    public function testANonResourceIsRefusedRatherThanAssumedClean(): void
    {
        $scanner = new ClamAvAttachmentScanner('tcp://scanner:3310', new NullLogger(), 5, fn () => false);

        /** @phpstan-ignore-next-line deliberately passing a non-resource */
        self::assertSame(AttachmentScanVerdict::Unavailable, $scanner->scan('not a stream'));
    }

    public function testAnUnreachableDaemonIsUnavailableRatherThanClean(): void
    {
        $scanner = new ClamAvAttachmentScanner('tcp://scanner:3310', new NullLogger(), 5, static fn () => false);

        self::assertSame(AttachmentScanVerdict::Unavailable, $scanner->scan($this->content('anything')));
    }

    public function testACleanVerdictReleasesTheAttachment(): void
    {
        $verdict = $this->scanAgainstDaemon('harmless bytes', "stream: OK\0");

        self::assertSame(AttachmentScanVerdict::Clean, $verdict);
    }

    public function testAnInfectedVerdictIsReported(): void
    {
        $verdict = $this->scanAgainstDaemon('suspicious bytes', "stream: Eicar-Test-Signature FOUND\0");

        self::assertSame(AttachmentScanVerdict::Infected, $verdict);
    }

    public function testAnErrorReplyIsTreatedAsNoAnswerAtAll(): void
    {
        // An error is never a release: only an explicit OK frees the file.
        $verdict = $this->scanAgainstDaemon('oversized', "INSTREAM size limit exceeded. ERROR\0");

        self::assertSame(AttachmentScanVerdict::Unavailable, $verdict);
    }

    public function testAnUnrecognisedReplyIsUnavailable(): void
    {
        $verdict = $this->scanAgainstDaemon('bytes', "something entirely unexpected\0");

        self::assertSame(AttachmentScanVerdict::Unavailable, $verdict);
    }

    public function testAnEmptyReplyIsUnavailable(): void
    {
        $verdict = $this->scanAgainstDaemon('bytes', '');

        self::assertSame(AttachmentScanVerdict::Unavailable, $verdict);
    }

    public function testTheDaemonReceivesLengthPrefixedBytesAndNothingElse(): void
    {
        $payload = str_repeat('A', 70000);

        $this->scanAgainstDaemon($payload, "stream: OK\0");

        self::assertIsResource($this->daemonSide);
        $received = (string) stream_get_contents($this->daemonSide);
        // The command, a full chunk, the remainder, then the zero terminator —
        // and no filename, report reference or professional identity anywhere.
        self::assertSame(
            "zINSTREAM\0"
            .pack('N', 65536).str_repeat('A', 65536)
            .pack('N', 70000 - 65536).str_repeat('A', 70000 - 65536)
            .pack('N', 0),
            $received,
        );
    }

    /**
     * Runs a full scan against a socket pair whose daemon side has the reply
     * queued, then closed so the scanner's read terminates.
     */
    private function scanAgainstDaemon(string $payload, string $reply): AttachmentScanVerdict
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$clientSide, $daemonSide] = $pair;
        $this->daemonSide = $daemonSide;

        if ($reply !== '') {
            fwrite($daemonSide, $reply);
        }
        // Half-close so the scanner's stream_get_contents returns instead of
        // blocking, which is what the daemon does after answering.
        stream_socket_shutdown($daemonSide, STREAM_SHUT_WR);

        $scanner = new ClamAvAttachmentScanner(
            'tcp://scanner:3310',
            new NullLogger(),
            5,
            static fn () => $clientSide,
        );

        return $scanner->scan($this->content($payload));
    }

    /** @return resource */
    private function content(string $bytes)
    {
        $handle = fopen('php://temp', 'r+');
        self::assertIsResource($handle);
        fwrite($handle, $bytes);
        rewind($handle);

        return $handle;
    }
}
