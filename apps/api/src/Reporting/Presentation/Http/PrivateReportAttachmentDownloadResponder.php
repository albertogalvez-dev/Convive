<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Application\AttachmentDownloadConcurrencyLimiter;
use App\Reporting\Domain\ReportAttachment;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PrivateReportAttachmentDownloadResponder
{
    private const BUFFER_BYTES = 8192;

    public function __construct(
        private AttachmentStorage $storage,
        private AttachmentDownloadConcurrencyLimiter $downloadConcurrencyLimiter,
    ) {
    }

    public function respond(ReportAttachment $attachment): StreamedResponse
    {
        if (!$attachment->isAvailable()) {
            throw new ReportAttachmentUnavailableHttpException();
        }

        $permit = $this->downloadConcurrencyLimiter->acquire();

        try {
            $content = $this->storage->open($attachment);
        } catch (\Throwable $exception) {
            $permit->release();

            throw new ReportAttachmentUnavailableHttpException(previous: $exception);
        }

        $filename = sprintf(
            'convive-evidence-%s.%s',
            $attachment->id()->toRfc4122(),
            $attachment->mediaType()->extension(),
        );

        return new StreamedResponse(
            function () use ($content, $permit): void {
                try {
                    while (!feof($content)) {
                        $chunk = fread($content, self::BUFFER_BYTES);

                        if ($chunk === false) {
                            throw new RuntimeException('The private attachment stream cannot be read.');
                        }

                        if ($chunk !== '') {
                            echo $chunk;
                        }
                    }
                } finally {
                    fclose($content);
                    $permit->release();
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type' => $attachment->mediaType()->value,
                'Content-Length' => (string) $attachment->byteSize(),
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
                'Content-Security-Policy' => "sandbox; default-src 'none'",
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ],
        );
    }
}
