<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Application\AttachmentStorageLimitExceeded;
use App\Reporting\Application\StoredAttachment;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentPolicy;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Development and test implementation of a private, application-mediated
 * attachment store. It intentionally has no public URL or web-root path.
 */
final class LocalPrivateAttachmentStorage implements AttachmentStorage
{
    private const BUFFER_BYTES = 8192;

    private string $directory;

    public function __construct(
        #[Autowire(param: 'attachment_storage.directory')]
        string $directory,
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDirectory,
    ) {
        $this->directory = $this->initialisePrivateDirectory(
            $directory,
            $projectDirectory,
        );
    }

    public function storeQuarantine(Uuid $attachmentId, string $sourcePath): StoredAttachment
    {
        $source = @fopen($sourcePath, 'rb');

        if ($source === false) {
            throw new RuntimeException('The attachment source cannot be read.');
        }

        $targetPath = $this->pathForKey(
            ReportAttachment::quarantineStorageKey($attachmentId),
        );
        $this->ensureDirectory(dirname($targetPath));
        $target = @fopen($targetPath, 'x+b');

        if ($target === false) {
            fclose($source);

            throw new RuntimeException('The private attachment target cannot be created.');
        }

        $bytes = 0;
        $hash = hash_init('sha256');

        try {
            while (!feof($source)) {
                $chunk = fread($source, self::BUFFER_BYTES);

                if ($chunk === false) {
                    throw new RuntimeException('The attachment source cannot be streamed.');
                }

                if ($chunk === '') {
                    continue;
                }

                $bytes += strlen($chunk);

                if ($bytes > ReportAttachmentPolicy::MAXIMUM_FILE_BYTES) {
                    throw new AttachmentStorageLimitExceeded('The attachment exceeds the accepted byte limit.');
                }

                hash_update($hash, $chunk);
                $this->writeAll($target, $chunk);
            }

            if ($bytes === 0) {
                throw new RuntimeException('The attachment must not be empty.');
            }

            $contentHash = hash_final($hash);
        } catch (\Throwable $exception) {
            fclose($source);
            fclose($target);
            @unlink($targetPath);

            throw $exception;
        }

        fclose($source);
        fclose($target);

        if (!chmod($targetPath, 0600)) {
            @unlink($targetPath);

            throw new RuntimeException('The private attachment permissions could not be set.');
        }

        return new StoredAttachment($bytes, $contentHash);
    }

    public function open(ReportAttachment $attachment)
    {
        $stream = @fopen($this->pathForKey($attachment->storageKey()), 'rb');

        if ($stream === false) {
            throw new RuntimeException('The private attachment object is unavailable.');
        }

        return $stream;
    }

    public function promoteToAvailable(ReportAttachment $attachment): void
    {
        $sourcePath = $this->pathForKey($attachment->storageKey());
        $targetPath = $this->pathForKey(
            ReportAttachment::availableStorageKey($attachment->id()),
        );
        $this->ensureDirectory(dirname($targetPath));

        if (is_file($targetPath) && !is_file($sourcePath)) {
            return;
        }

        if (!is_file($sourcePath) || is_file($targetPath) || !@rename($sourcePath, $targetPath)) {
            throw new RuntimeException('The private attachment could not be promoted.');
        }

        if (!chmod($targetPath, 0600)) {
            throw new RuntimeException('The promoted attachment permissions could not be set.');
        }
    }

    public function delete(ReportAttachment $attachment): void
    {
        $path = $this->pathForKey($attachment->storageKey());

        if (is_file($path) && !@unlink($path)) {
            throw new RuntimeException('The private attachment object could not be deleted.');
        }
    }

    private function initialisePrivateDirectory(
        string $directory,
        string $projectDirectory,
    ): string {
        $this->ensureDirectory($directory);
        $resolvedDirectory = realpath($directory);
        $publicDirectory = realpath($projectDirectory.'/public');

        if ($resolvedDirectory === false || $publicDirectory === false) {
            throw new RuntimeException('The private attachment storage cannot be resolved.');
        }

        $resolvedDirectory = rtrim($resolvedDirectory, DIRECTORY_SEPARATOR);
        $publicDirectory = rtrim($publicDirectory, DIRECTORY_SEPARATOR);

        if (
            $resolvedDirectory === $publicDirectory
            || str_starts_with($resolvedDirectory, $publicDirectory.DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('Attachment storage must remain outside the web root.');
        }

        $this->ensureDirectory($resolvedDirectory.'/quarantine');
        $this->ensureDirectory($resolvedDirectory.'/available');

        return $resolvedDirectory;
    }

    private function pathForKey(string $storageKey): string
    {
        if (
            preg_match(
                '#\A(?:quarantine|available)/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z#D',
                $storageKey,
            ) !== 1
        ) {
            throw new RuntimeException('The attachment storage key is invalid.');
        }

        return $this->directory.DIRECTORY_SEPARATOR.str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $storageKey,
        );
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('The private attachment directory cannot be created.');
        }

        if (!chmod($directory, 0700)) {
            throw new RuntimeException('The private attachment directory permissions could not be set.');
        }
    }

    /** @param resource $target */
    private function writeAll($target, string $chunk): void
    {
        $offset = 0;
        $length = strlen($chunk);

        while ($offset < $length) {
            $written = fwrite($target, substr($chunk, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('The private attachment object cannot be written.');
            }

            $offset += $written;
        }
    }
}
