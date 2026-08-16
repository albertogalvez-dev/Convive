<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Organisations\Application\ManageReportingChannel;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalRole;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

/**
 * Administration of a centre's public reporting link, limited to an
 * administrator of that centre.
 */
final readonly class ReportingChannelController
{
    public function __construct(
        private ManageReportingChannel $manageChannel,
        private OrganisationRepository $organisations,
        private OrganisationMembershipRepository $memberships,
    ) {
    }

    #[Route('/api/v1/professional/organisations/{id}/reporting-channel', name: 'api_v1_professional_get_reporting_channel', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getOrganisationReportingChannel',
        summary: 'Read the public reporting channel of an administered centre',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The current link and its state.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The organisation is not administered by this professional.'),
        ],
    )]
    public function show(string $id, #[CurrentUser] Professional $professional): JsonResponse
    {
        return $this->json($this->administeredOrganisation($id, $professional));
    }

    #[Route('/api/v1/professional/organisations/{id}/reporting-channel', name: 'api_v1_professional_change_reporting_channel', methods: ['PATCH'])]
    #[OA\Patch(
        operationId: 'changeOrganisationReportingChannel',
        summary: 'Pause, activate, rotate or retire the public reporting channel',
        description: 'Changes routing for new reports only. A reporter holding an access code reaches their own report through a route that never takes a centre identifier, so none of these actions affects a conversation already under way.',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The updated link and state.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'The action is not possible in the current state.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The organisation is not administered by this professional.'),
        ],
    )]
    public function change(
        string $id,
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')] ChangeReportingChannelRequest $payload,
    ): JsonResponse {
        $organisation = $this->administeredOrganisation($id, $professional);

        try {
            match ($payload->action) {
                'pause' => $this->manageChannel->pause($organisation),
                'activate' => $this->manageChannel->activate($organisation),
                'rotate' => $this->manageChannel->rotate($organisation),
                'retire' => $this->manageChannel->retire($organisation),
                default => throw new BadRequestHttpException('The reporting-channel action is invalid.'),
            };
        } catch (LogicException $exception) {
            throw new BadRequestHttpException('The reporting-channel action is not possible in the current state.', $exception);
        }

        return $this->json($organisation);
    }

    private function administeredOrganisation(string $id, Professional $professional): Organisation
    {
        if (!Uuid::isValid($id)) {
            throw new NotFoundHttpException('The organisation is not available.');
        }

        $organisation = $this->organisations->findById(Uuid::fromString($id));
        if ($organisation === null) {
            throw new NotFoundHttpException('The organisation is not available.');
        }

        // An administrator of another centre gets the same response as one that
        // does not exist, so administering nothing reveals nothing.
        if ($this->memberships->findActiveByProfessionalAndOrganisation($professional, $organisation, ProfessionalRole::Administrator) === null) {
            throw new NotFoundHttpException('The organisation is not available.');
        }

        return $organisation;
    }

    private function json(Organisation $organisation): JsonResponse
    {
        return new JsonResponse(
            [
                'identifier' => $organisation->publicReportingIdentifier()->toString(),
                'status' => $organisation->reportingChannelStatus()->value,
                'acceptsNewReports' => $organisation->acceptsNewReports(),
            ],
            Response::HTTP_OK,
            ['Cache-Control' => 'no-store, private'],
        );
    }
}
