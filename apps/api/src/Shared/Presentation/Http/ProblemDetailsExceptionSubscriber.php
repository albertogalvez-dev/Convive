<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Organisations\Application\GetPublicReportingProfile\PublicReportingOrganisationNotFound;
use App\Organisations\Presentation\Http\PublicReportingOrganisationNotFoundHttpException;
use App\Professionals\Presentation\Http\InvalidProfessionalReportRequestHttpException;
use App\Professionals\Presentation\Http\InvalidProfessionalReportResponseHttpException;
use App\Professionals\Presentation\Http\InvalidProfessionalReportTriageHttpException;
use App\Professionals\Presentation\Http\ProfessionalReportAlreadyReviewedHttpException;
use App\Professionals\Presentation\Http\ProfessionalReportNotFoundHttpException;
use App\Professionals\Presentation\Http\ProfessionalEmailConflictHttpException;
use App\Professionals\Presentation\Http\ProfessionalReportTriageConflictHttpException;
use App\Reporting\Application\AddReportFollowUpEntry\ReportFollowUpEntryLimitReached;
use App\Reporting\Application\AttachmentStorageLimitExceeded;
use App\Reporting\Application\AttachmentDownloadConcurrencyLimitReached;
use App\Reporting\Application\QuarantineReportAttachments\AttachmentUploadCountExceeded;
use App\Reporting\Application\SubmitAnonymousReport\ReportingOrganisationNotFound;
use App\Reporting\Application\VerifyReportAccess\ReportAccessDenied;
use App\Reporting\Domain\ReportAttachmentQuotaExceeded;
use App\Reporting\Presentation\Http\AttachmentUploadInvalidHttpException;
use App\Reporting\Presentation\Http\AttachmentUploadTooLargeHttpException;
use App\Reporting\Presentation\Http\AttachmentUploadUnsupportedMediaTypeHttpException;
use App\Reporting\Presentation\Http\InvalidFollowUpEntryHttpException;
use App\Reporting\Presentation\Http\PublicReportingUnavailableHttpException;
use App\Reporting\Presentation\Http\ReportAccessCapabilityRejectedHttpException;
use App\Reporting\Presentation\Http\ReportAccessDeniedHttpException;
use App\Reporting\Presentation\Http\ReportAttachmentUnavailableHttpException;
use App\Reporting\Presentation\Http\ReportingOrganisationNotFoundHttpException;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ProblemDetailsExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SecurityEventLogger $securityEventLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with(
            $event->getRequest()->getPathInfo(),
            '/api/',
        )) {
            return;
        }

        $exception = $event->getThrowable();

        if (
            $exception instanceof ReportingOrganisationNotFound
            || $exception instanceof PublicReportingOrganisationNotFound
            || $exception instanceof ReportingOrganisationNotFoundHttpException
            || $exception
                instanceof PublicReportingOrganisationNotFoundHttpException
        ) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:reporting-organisation-not-found',
                    'Reporting organisation not found',
                    Response::HTTP_NOT_FOUND,
                    'The requested reporting organisation was not found.',
                ),
            );

            return;
        }

        if (
            $exception instanceof ReportAccessDenied
            || $exception instanceof ReportAccessDeniedHttpException
        ) {
            $this->securityEventLogger->reportAccessDenied(
                $event->getRequest(),
            );
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:report-access-denied',
                    'Report access denied',
                    Response::HTTP_UNAUTHORIZED,
                    'The report access secret was not accepted.',
                ),
            );

            return;
        }

        if ($exception instanceof ReportAccessCapabilityRejectedHttpException) {
            $this->securityEventLogger->reportAccessDenied(
                $event->getRequest(),
            );
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:report-access-capability-denied',
                    'Report access capability denied',
                    Response::HTTP_UNAUTHORIZED,
                    'The report access capability was not accepted.',
                ),
            );

            return;
        }

        if ($exception instanceof PublicReportingUnavailableHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:public-reporting-unavailable',
                    'Public reporting unavailable',
                    Response::HTTP_FORBIDDEN,
                    'This fictional demonstration does not accept communications or follow-up information.',
                ),
            );

            return;
        }

        if ($exception instanceof AttachmentDownloadConcurrencyLimitReached) {
            $this->securityEventLogger->attachmentDownloadConcurrencyLimited($event->getRequest());
            $response = $this->createResponse(
                'urn:convive:problem:attachment-download-busy',
                'Attachment download busy',
                Response::HTTP_TOO_MANY_REQUESTS,
                'The attachment download capacity is temporarily full. Try again shortly.',
            );
            $response->headers->set('Retry-After', '1');
            $event->setResponse($response);

            return;
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            $response = $this->createResponse(
                'urn:convive:problem:rate-limited',
                'Too many requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                'Too many requests. Try again later.',
            );

            $retryAfter = $exception->getHeaders()['Retry-After'] ?? null;

            if ($retryAfter !== null) {
                $response->headers->set(
                    'Retry-After',
                    (string) $retryAfter,
                );
            }

            $event->setResponse($response);

            return;
        }

        if ($exception instanceof ReportFollowUpEntryLimitReached) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:follow-up-entry-limit-reached',
                    'Follow-up entry limit reached',
                    Response::HTTP_CONFLICT,
                    'No more information can be added to this report.',
                ),
            );

            return;
        }

        if ($exception instanceof InvalidFollowUpEntryHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:invalid-follow-up-entry',
                    'Invalid follow-up entry',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The submitted follow-up entry is invalid.',
                ),
            );

            return;
        }

        if ($exception instanceof AttachmentUploadUnsupportedMediaTypeHttpException) {
            $this->securityEventLogger->attachmentUploadRejected($event->getRequest());
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:attachment-type-not-accepted',
                    'Attachment type not accepted',
                    Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'The submitted attachment type is not accepted.',
                ),
            );

            return;
        }

        if ($exception instanceof AttachmentUploadTooLargeHttpException || $exception instanceof AttachmentStorageLimitExceeded) {
            $this->securityEventLogger->attachmentUploadRejected($event->getRequest());
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:attachment-too-large',
                    'Attachment too large',
                    413,
                    'The submitted attachment exceeds the accepted size.',
                ),
            );

            return;
        }

        if ($exception instanceof AttachmentUploadInvalidHttpException || $exception instanceof AttachmentUploadCountExceeded) {
            $this->securityEventLogger->attachmentUploadRejected($event->getRequest());
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:invalid-attachment-upload',
                    'Invalid attachment upload',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The attachment upload could not be accepted.',
                ),
            );

            return;
        }

        if ($exception instanceof ReportAttachmentQuotaExceeded) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:attachment-capacity-reached',
                    'Attachment capacity reached',
                    Response::HTTP_CONFLICT,
                    'This report cannot accept more attachment data.',
                ),
            );

            return;
        }

        if ($exception instanceof ReportAttachmentUnavailableHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:attachment-unavailable',
                    'Attachment unavailable',
                    Response::HTTP_NOT_FOUND,
                    'The requested attachment is unavailable.',
                ),
            );

            return;
        }

        if ($exception instanceof ProfessionalReportNotFoundHttpException) {
            $this->securityEventLogger->professionalReportAccessDenied(
                $event->getRequest(),
            );
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:professional-report-not-found',
                    'Professional report not found',
                    Response::HTTP_NOT_FOUND,
                    'The requested report is not available in this professional scope.',
                ),
            );

            return;
        }

        if ($exception instanceof ProfessionalReportAlreadyReviewedHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:report-already-reviewed',
                    'Report already reviewed',
                    Response::HTTP_CONFLICT,
                    'The report has already received its initial review.',
                ),
            );

            return;
        }

        if ($exception instanceof InvalidProfessionalReportRequestHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:invalid-report-review',
                    'Invalid report review',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The submitted report review is invalid.',
                ),
            );

            return;
        }

        if ($exception instanceof InvalidProfessionalReportResponseHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:invalid-professional-report-response',
                    'Invalid professional report response',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The submitted professional response is invalid.',
                ),
            );

            return;
        }

        if ($exception instanceof InvalidProfessionalReportTriageHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:invalid-report-triage',
                    'Invalid report triage',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The submitted report triage decision is invalid.',
                ),
            );

            return;
        }

        if ($exception instanceof ProfessionalEmailConflictHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:professional-email-conflict',
                    'Professional email conflict',
                    Response::HTTP_CONFLICT,
                    'The professional email address is already in use.',
                ),
            );

            return;
        }

        if ($exception instanceof ProfessionalReportTriageConflictHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:report-triage-conflict',
                    'Report triage conflict',
                    Response::HTTP_CONFLICT,
                    'The report cannot accept this triage decision.',
                ),
            );

            return;
        }

        $validationException = $exception->getPrevious();

        if ($validationException instanceof ValidationFailedException) {
            $errors = [];

            foreach ($validationException->getViolations() as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }

            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:request-validation-failed',
                    'Request validation failed',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The request contains invalid fields.',
                    $errors,
                ),
            );

            return;
        }

        if ($exception instanceof BadRequestHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:malformed-json',
                    'Malformed JSON',
                    Response::HTTP_BAD_REQUEST,
                    'The request body must contain valid JSON.',
                ),
            );

            return;
        }

        if ($exception instanceof UnsupportedMediaTypeHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:unsupported-media-type',
                    'Unsupported media type',
                    Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    'The request content type must be application/json.',
                ),
            );

            return;
        }

        if ($exception instanceof UnprocessableEntityHttpException) {
            $event->setResponse(
                $this->createResponse(
                    'urn:convive:problem:invalid-report-submission',
                    'Invalid report submission',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'The request contains invalid report information.',
                ),
            );

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            $event->setResponse(
                $this->createResponse(
                    'about:blank',
                    Response::$statusTexts[$status] ?? 'HTTP error',
                    $status,
                    'The request could not be completed.',
                ),
            );
        }
    }

    /**
     * @param list<array{field: string, message: string}> $errors
     */
    private function createResponse(
        string $type,
        string $title,
        int $status,
        string $detail,
        array $errors = [],
    ): JsonResponse {
        $body = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse(
            $body,
            $status,
            [
                'Content-Type' => 'application/problem+json',
                'Cache-Control' => 'no-store',
            ],
        );
    }
}
