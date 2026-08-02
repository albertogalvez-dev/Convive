<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReport;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReportCommand;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use ValueError;

final readonly class SubmitAnonymousReportController
{
    public function __construct(
        private SubmitAnonymousReport $submitAnonymousReport,
    ) {
    }

    #[Route(
        '/api/v1/public/organisations/{publicReportingIdentifier}/reports',
        name: 'api_v1_public_submit_anonymous_report',
        methods: ['POST'],
        format: 'json',
    )]
    public function __invoke(
        string $publicReportingIdentifier,
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        SubmitAnonymousReportRequest $request,
    ): JsonResponse {
        try {
            $organisationIdentifier = PublicReportingIdentifier::fromString(
                $publicReportingIdentifier,
            );
        } catch (InvalidArgumentException $exception) {
            throw new ReportingOrganisationNotFoundHttpException(
                'Reporting organisation not found.',
                previous: $exception,
            );
        }

        try {
            $situationDescription = SituationDescription::fromString(
                $request->situationDescription,
            );
            $situationContext = SituationContext::from(
                $request->situationContext,
            );
        } catch (InvalidArgumentException|ValueError $exception) {
            throw new UnprocessableEntityHttpException(
                'The request contains invalid report information.',
                previous: $exception,
            );
        }

        $result = ($this->submitAnonymousReport)(
            new SubmitAnonymousReportCommand(
                $organisationIdentifier,
                $situationDescription,
                $situationContext,
            ),
        );

        return new JsonResponse(
            [
                'publicReference' => $result->publicReference,
                'accessSecret' => $result->plainAccessSecret,
                'status' => $result->status->value,
                'createdAt' => $result->createdAt->format(
                    DATE_RFC3339_EXTENDED,
                ),
            ],
            Response::HTTP_CREATED,
            [
                'Cache-Control' => 'no-store',
            ],
        );
    }
}
