<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentScanner;
use App\Reporting\Application\AttachmentScanVerdict;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Scans one private object stream against an isolated ClamAV daemon using the
 * INSTREAM command, which carries the bytes and nothing else: no filename, no
 * report reference and no professional identity ever reaches the scanner.
 *
 * Every failure mode returns `Unavailable` rather than `Clean`. A file can only
 * become readable through an explicit clean verdict from a daemon that actually
 * answered, so an unreachable, slow or misbehaving scanner keeps the attachment
 * quarantined instead of releasing it.
 */
final readonly class ClamAvAttachmentScanner implements AttachmentScanner
{
    /** clamd rejects a stream chunk larger than its StreamMaxLength; stay well below any sane value. */
    private const CHUNK_BYTES = 65536;

    /**
     * @param (Closure(string, int): (resource|false))|null $connect overrides how the
     *        daemon socket is opened; production leaves it null and uses stream_socket_client
     */
    public function __construct(
        private string $dsn,
        private LoggerInterface $logger,
        private int $timeoutSeconds = 30,
        private ?Closure $connect = null,
    ) {
    }

    public function scan($content): AttachmentScanVerdict
    {
        if ($this->dsn === '') {
            // No daemon is provisioned in this environment. Attachments stay
            // quarantined rather than being released unscanned.
            return AttachmentScanVerdict::Unavailable;
        }

        if (!is_resource($content)) {
            return AttachmentScanVerdict::Unavailable;
        }

        $socket = $this->openSocket();
        if ($socket === false) {
            return AttachmentScanVerdict::Unavailable;
        }

        try {
            return $this->exchange($socket, $content);
        } finally {
            fclose($socket);
        }
    }

    /** @return resource|false */
    private function openSocket()
    {
        if ($this->connect !== null) {
            return ($this->connect)($this->dsn, $this->timeoutSeconds);
        }

        $socket = @stream_socket_client(
            $this->dsn,
            $errorCode,
            $errorMessage,
            $this->timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            // Deliberately not logging the DSN or any attachment detail.
            $this->logger->error('The attachment scanner is unreachable.', [
                'error_code' => $errorCode,
            ]);
        }

        return $socket;
    }

    /**
     * @param resource $socket
     * @param resource $content
     */
    private function exchange($socket, $content): AttachmentScanVerdict
    {
        stream_set_timeout($socket, $this->timeoutSeconds);

        if (@fwrite($socket, "zINSTREAM\0") === false) {
            return AttachmentScanVerdict::Unavailable;
        }

        rewind($content);
        while (!feof($content)) {
            $chunk = fread($content, self::CHUNK_BYTES);
            if ($chunk === false) {
                return AttachmentScanVerdict::Unavailable;
            }
            if ($chunk === '') {
                continue;
            }

            // Each chunk is a four-byte network-order length followed by the bytes.
            if (@fwrite($socket, pack('N', strlen($chunk)).$chunk) === false) {
                return AttachmentScanVerdict::Unavailable;
            }
        }

        // A zero-length chunk terminates the stream.
        if (@fwrite($socket, pack('N', 0)) === false) {
            return AttachmentScanVerdict::Unavailable;
        }

        $response = @stream_get_contents($socket);
        if ($response === false || stream_get_meta_data($socket)['timed_out']) {
            $this->logger->error('The attachment scanner did not answer in time.');

            return AttachmentScanVerdict::Unavailable;
        }

        return $this->interpret(trim($response));
    }

    private function interpret(string $response): AttachmentScanVerdict
    {
        if (str_ends_with($response, 'OK')) {
            return AttachmentScanVerdict::Clean;
        }

        if (str_ends_with($response, 'FOUND')) {
            return AttachmentScanVerdict::Infected;
        }

        // Anything else — an ERROR line, a truncated reply, an unexpected
        // protocol change — is treated as no answer at all.
        $this->logger->error('The attachment scanner returned an unusable verdict.');

        return AttachmentScanVerdict::Unavailable;
    }
}
