<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Infrastructure\LocalPrivateAttachmentStorage;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class LocalPrivateAttachmentStorageTest extends TestCase
{
    private string $storageDirectory;

    /** @var list<string> */
    private array $sourcePaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageDirectory = sys_get_temp_dir().'/convive-attachments-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        foreach ($this->sourcePaths as $sourcePath) {
            @unlink($sourcePath);
        }

        $this->deleteDirectory($this->storageDirectory);

        parent::tearDown();
    }

    public function testItStreamsPrivateBytesWithoutUsingTheClientFilename(): void
    {
        $storage = $this->storage();
        $source = $this->sourceFile("%PDF-1.7\nfictional evidence\n");
        $attachmentId = Uuid::v7();

        $stored = $storage->storeQuarantine($attachmentId, $source);
        $attachment = ReportAttachment::quarantine(
            $attachmentId,
            $this->createReport(),
            AttachmentMediaType::Pdf,
            $stored->byteSize,
            $stored->contentHash,
            new DateTimeImmutable('2026-08-10T19:00:00+00:00'),
        );

        self::assertSame(
            strlen("%PDF-1.7\nfictional evidence\n"),
            $stored->byteSize,
        );
        self::assertSame(
            hash('sha256', "%PDF-1.7\nfictional evidence\n"),
            $stored->contentHash,
        );
        self::assertFileDoesNotExist($this->storageDirectory.'/report.pdf');

        $stream = $storage->open($attachment);
        self::assertSame("%PDF-1.7\nfictional evidence\n", stream_get_contents($stream));
        fclose($stream);

        $attachment->beginScanning(new DateTimeImmutable('2026-08-10T19:01:00+00:00'));
        $storage->promoteToAvailable($attachment);
        $attachment->markAvailable(new DateTimeImmutable('2026-08-10T19:02:00+00:00'));

        $available = $storage->open($attachment);
        self::assertSame("%PDF-1.7\nfictional evidence\n", stream_get_contents($available));
        fclose($available);

        $storage->delete($attachment);

        $this->expectException(\RuntimeException::class);
        $storage->open($attachment);
    }

    public function testItRejectsBytesThatExceedTheServerSideLimit(): void
    {
        $storage = $this->storage();
        $source = $this->sourceFile(
            str_repeat('a', ReportAttachmentPolicy::MAXIMUM_FILE_BYTES + 1),
        );

        $this->expectException(\App\Reporting\Application\AttachmentStorageLimitExceeded::class);

        $storage->storeQuarantine(Uuid::v7(), $source);
    }

    public function testItRejectsAStorageDirectoryInsideTheWebRoot(): void
    {
        $projectDirectory = dirname(__DIR__, 3);
        $unsafeDirectory = $projectDirectory.'/public/private-attachments';

        $this->expectException(\RuntimeException::class);

        try {
            new LocalPrivateAttachmentStorage($unsafeDirectory, $projectDirectory);
        } finally {
            @rmdir($unsafeDirectory);
        }
    }

    private function storage(): LocalPrivateAttachmentStorage
    {
        return new LocalPrivateAttachmentStorage(
            $this->storageDirectory,
            dirname(__DIR__, 3),
        );
    }

    private function sourceFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-attachment-source-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);
        $this->sourcePaths[] = $path;

        return $path;
    }

    private function createReport(): Report
    {
        return Report::create(
            new Organisation(
                Uuid::v7(),
                'IES Attachment Storage',
                PublicReportingIdentifier::generate(),
            ),
            SituationDescription::fromString('A fictional private storage test.'),
            SituationContext::Digital,
        )->report;
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        self::assertIsArray($items);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
