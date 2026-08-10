<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentDownloadConcurrencyLimitReached;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Infrastructure\LocalPrivateAttachmentDownloadConcurrencyLimiter;
use App\Reporting\Infrastructure\LocalPrivateAttachmentStorage;
use PHPUnit\Framework\TestCase;

final class LocalPrivateAttachmentDownloadConcurrencyLimiterTest extends TestCase
{
    private string $storageDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageDirectory = sys_get_temp_dir().'/convive-attachment-downloads-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->storageDirectory);
        parent::tearDown();
    }

    public function testItAllowsOnlyTheBoundedNumberOfSimultaneousPrivateStreams(): void
    {
        $limiter = new LocalPrivateAttachmentDownloadConcurrencyLimiter($this->storage());
        $permits = [];

        for ($permit = 0; $permit < ReportAttachmentPolicy::MAXIMUM_CONCURRENT_DOWNLOADS; ++$permit) {
            $permits[] = $limiter->acquire();
        }

        try {
            $limiter->acquire();
            self::fail('The private download concurrency boundary was not enforced.');
        } catch (AttachmentDownloadConcurrencyLimitReached) {
            self::assertTrue(true);
        }

        $permits[0]->release();
        $replacement = $limiter->acquire();
        $replacement->release();
        $permits[0]->release();

        foreach (array_slice($permits, 1) as $permit) {
            $permit->release();
        }
    }

    private function storage(): LocalPrivateAttachmentStorage
    {
        return new LocalPrivateAttachmentStorage(
            $this->storageDirectory,
            dirname(__DIR__, 3),
        );
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        self::assertIsArray($entries);

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
