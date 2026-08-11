<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\QuarantinedAttachmentUpload;
use App\Reporting\Domain\AttachmentDescription;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\ReportAttachmentPolicy;
use finfo;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Normalises only server-validated multipart temporary files. Client names
 * and MIME headers intentionally never cross this presentation boundary.
 */
final readonly class ReportAttachmentUploadInspector
{
    /**
     * @return list<QuarantinedAttachmentUpload>
     */
    public function inspect(Request $request): array
    {
        if (!str_starts_with(
            (string) $request->headers->get('Content-Type'),
            'multipart/form-data',
        )) {
            throw new AttachmentUploadUnsupportedMediaTypeHttpException();
        }

        $files = $request->files->get('attachments');

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        if (!is_array($files) || $files === [] || !array_is_list($files)) {
            throw new AttachmentUploadInvalidHttpException();
        }

        if (count($files) > ReportAttachmentPolicy::MAXIMUM_ATTACHMENTS_PER_WRITE) {
            throw new AttachmentUploadInvalidHttpException();
        }

        $descriptions = $this->descriptions($request, count($files));
        $uploads = [];

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                throw new AttachmentUploadInvalidHttpException();
            }

            $this->assertValidSize($file);
            $uploads[] = new QuarantinedAttachmentUpload(
                $file->getPathname(),
                $this->detectMediaType($file),
                $descriptions[$index],
            );
        }

        return $uploads;
    }

    /** @return list<?AttachmentDescription> */
    private function descriptions(Request $request, int $attachmentCount): array
    {
        $formFields = $request->request->all();
        $rawDescriptions = $formFields['descriptions'] ?? null;

        if ($rawDescriptions === null) {
            return array_fill(0, $attachmentCount, null);
        }

        if (
            !is_array($rawDescriptions)
            || !array_is_list($rawDescriptions)
            || count($rawDescriptions) !== $attachmentCount
        ) {
            throw new AttachmentUploadInvalidHttpException();
        }

        $descriptions = [];

        foreach ($rawDescriptions as $description) {
            if (!is_string($description)) {
                throw new AttachmentUploadInvalidHttpException();
            }

            try {
                $descriptions[] = AttachmentDescription::fromNullable($description);
            } catch (InvalidArgumentException $exception) {
                throw new AttachmentUploadInvalidHttpException(previous: $exception);
            }
        }

        return $descriptions;
    }

    private function assertValidSize(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            if (in_array(
                $file->getError(),
                [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE],
                true,
            )) {
                throw new AttachmentUploadTooLargeHttpException();
            }

            throw new AttachmentUploadInvalidHttpException();
        }

        $size = $file->getSize();

        if ($size === false || $size < 1) {
            throw new AttachmentUploadInvalidHttpException();
        }

        if ($size > ReportAttachmentPolicy::MAXIMUM_FILE_BYTES) {
            throw new AttachmentUploadTooLargeHttpException();
        }
    }

    private function detectMediaType(UploadedFile $file): AttachmentMediaType
    {
        $path = $file->getPathname();
        $header = file_get_contents($path, false, null, 0, 16);
        $detectedType = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (!is_string($header) || !is_string($detectedType)) {
            throw new AttachmentUploadInvalidHttpException();
        }

        return match (true) {
            str_starts_with($header, '%PDF-')
                && $detectedType === AttachmentMediaType::Pdf->value
                => AttachmentMediaType::Pdf,
            str_starts_with($header, "\xFF\xD8\xFF")
                && $detectedType === AttachmentMediaType::Jpeg->value
                => AttachmentMediaType::Jpeg,
            str_starts_with($header, "\x89PNG\r\n\x1a\n")
                && $detectedType === AttachmentMediaType::Png->value
                => AttachmentMediaType::Png,
            default => throw new AttachmentUploadUnsupportedMediaTypeHttpException(),
        };
    }
}
