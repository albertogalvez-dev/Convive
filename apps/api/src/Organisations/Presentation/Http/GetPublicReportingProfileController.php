<?php

declare(strict_types=1);

namespace App\Organisations\Presentation\Http;

use App\Organisations\Application\GetPublicReportingProfile\GetPublicReportingProfile;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\PublicReportingModePolicy;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetPublicReportingProfileController
{
    public function __construct(
        private GetPublicReportingProfile $getPublicReportingProfile,
        private PublicReportingModePolicy $publicReportingModePolicy,
    ) {
    }

    #[Route(
        '/api/v1/public/organisations/{publicReportingIdentifier}',
        name: 'api_v1_public_get_reporting_profile',
        methods: ['GET'],
        format: 'json',
    )]
    #[OA\Get(
        operationId: 'getPublicReportingProfile',
        summary: 'Get the public reporting profile for an organisation',
        description: 'Returns the organisation name displayed before the anonymous reporting form.',
        security: [],
        tags: ['Public reporting'],
        parameters: [
            new OA\Parameter(
                name: 'publicReportingIdentifier',
                description: 'Canonical public reporting identifier of the organisation.',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'string',
                    pattern: '^ORG_[0-9A-HJKMNP-TV-Z]{16}$',
                    example: 'ORG_EZ8E3G9CR2F7VGDF',
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'The public reporting profile.',
                content: new OA\JsonContent(
                    required: ['name', 'reportingMode'],
                    properties: [
                        new OA\Property(
                            property: 'name',
                            type: 'string',
                            example: 'IES Valle Sereno',
                        ),
                        new OA\Property(
                            property: 'reportingMode',
                            type: 'string',
                            enum: ['operational', 'fictional_demo', 'disabled'],
                            example: 'operational',
                        ),
                    ],
                    type: 'object',
                    additionalProperties: false,
                ),
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'The public reporting identifier is invalid or the organisation was not found.',
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
    ): JsonResponse {
        try {
            $identifier = PublicReportingIdentifier::fromString(
                $publicReportingIdentifier,
            );
        } catch (InvalidArgumentException $exception) {
            throw new PublicReportingOrganisationNotFoundHttpException(
                'Reporting organisation not found.',
                previous: $exception,
            );
        }

        $profile = ($this->getPublicReportingProfile)($identifier);

        return new JsonResponse(
            [
                'name' => $profile->name,
                'reportingMode' => $this->publicReportingModePolicy->mode()->value,
            ],
            Response::HTTP_OK,
        );
    }
}
