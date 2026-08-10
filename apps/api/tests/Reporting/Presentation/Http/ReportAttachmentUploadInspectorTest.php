<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Presentation\Http\AttachmentUploadInvalidHttpException;
use App\Reporting\Presentation\Http\AttachmentUploadTooLargeHttpException;
use App\Reporting\Presentation\Http\AttachmentUploadUnsupportedMediaTypeHttpException;
use App\Reporting\Presentation\Http\ReportAttachmentUploadInspector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class ReportAttachmentUploadInspectorTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function testItUsesServerDetectionAndNeverTheClientFilenameOrMimeHeader(): void
    {
        $file = $this->upload('%PDF-1.7\nfictional evidence\n', 'evidence.exe', 'text/html');

        $uploads = (new ReportAttachmentUploadInspector())->inspect(
            $this->multipartRequest([$file]),
        );

        self::assertCount(1, $uploads);
        self::assertSame(AttachmentMediaType::Pdf, $uploads[0]->mediaType);
        self::assertSame($file->getPathname(), $uploads[0]->sourcePath);
        self::assertStringNotContainsString('evidence.exe', $uploads[0]->sourcePath);
    }

    public function testItRejectsSpoofedAndUnsupportedContent(): void
    {
        $file = $this->upload('not a PDF', 'evidence.pdf', 'application/pdf');

        $this->expectException(AttachmentUploadUnsupportedMediaTypeHttpException::class);

        (new ReportAttachmentUploadInspector())->inspect($this->multipartRequest([$file]));
    }

    public function testItRejectsAnEmptyOrMalformedMultipartFileCollection(): void
    {
        $this->expectException(AttachmentUploadInvalidHttpException::class);

        (new ReportAttachmentUploadInspector())->inspect($this->multipartRequest([]));
    }

    public function testItRejectsTooManyFilesBeforeTheyReachStorage(): void
    {
        $files = [];

        for ($index = 0; $index <= ReportAttachmentPolicy::MAXIMUM_ATTACHMENTS_PER_WRITE; ++$index) {
            $files[] = $this->upload('%PDF-1.7\nfictional evidence\n', sprintf('%d.pdf', $index));
        }

        $this->expectException(AttachmentUploadInvalidHttpException::class);

        (new ReportAttachmentUploadInspector())->inspect($this->multipartRequest($files));
    }

    public function testItRejectsAnOversizedTemporaryUploadBeforeDetection(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-attachment-');
        self::assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        $handle = fopen($path, 'c');
        self::assertNotFalse($handle);
        self::assertTrue(ftruncate($handle, ReportAttachmentPolicy::MAXIMUM_FILE_BYTES + 1));
        fclose($handle);
        $file = new UploadedFile($path, 'large.pdf', 'application/pdf', null, true);

        $this->expectException(AttachmentUploadTooLargeHttpException::class);

        (new ReportAttachmentUploadInspector())->inspect($this->multipartRequest([$file]));
    }

    /** @param list<UploadedFile> $files */
    private function multipartRequest(array $files): Request
    {
        return Request::create(
            '/api/v1/reporter/report/attachments',
            'POST',
            files: ['attachments' => $files],
            server: ['CONTENT_TYPE' => 'multipart/form-data; boundary=convive'],
        );
    }

    private function upload(
        string $content,
        string $clientName,
        string $clientMimeType = 'application/octet-stream',
    ): UploadedFile {
        $path = tempnam(sys_get_temp_dir(), 'convive-attachment-');
        self::assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        self::assertSame(strlen($content), file_put_contents($path, $content));

        return new UploadedFile($path, $clientName, $clientMimeType, null, true);
    }
}
