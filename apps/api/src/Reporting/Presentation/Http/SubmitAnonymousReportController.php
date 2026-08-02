<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReport;
use App\Reporting\Application\SubmitAnonymousReport\SubmitAnonymousReportCommand;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        operationId: 'submitAnonymousReport',
        summary: 'Submit an anonymous report',
        description: 'Creates an anonymous report for the selected organisation. The access secret is returned only once and must be stored securely by the reporter.',
        security: [],
        tags: ['Public reporting'],
        parameters: [
            new OA\Parameter(
                name: 'publicReportingIdentifier',
                description: 'Public identifier of the organisation receiving the report.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    pattern: '^ORG_[0-9A-HJKMNP-TV-Z]{16}$',
                    example: 'ORG_EZ8E3G9CR2F7VGDF',
                ),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: 'Anonymous report information.',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(
                    type: SubmitAnonymousReportRequest::class,
                ),
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'The anonymous report was created successfully.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/AnonymousReportSubmissionResponse',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'The request body contains malformed JSON.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'The reporting organisation does not exist.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                description: 'The request content type is not application/json.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'The submitted report information is invalid.',
                content: new OA\MediaType(
                    mediaType: 'application/problem+json',
                    schema: new OA\Schema(
                        ref: '#/components/schemas/ProblemDetails',
                    ),
                ),
            ),
        ],
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
