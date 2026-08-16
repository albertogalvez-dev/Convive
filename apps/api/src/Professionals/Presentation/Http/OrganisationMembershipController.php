<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Professionals\Application\ManageOrganisationMembership;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalRepository;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationMembershipController
{
    public function __construct(
        private OrganisationRepository $organisations,
        private OrganisationMembershipRepository $memberships,
        private ProfessionalRepository $professionals,
        private ManageOrganisationMembership $manager,
    ) {
    }

    #[Route('/api/v1/professional/organisations/{id}/memberships', name: 'api_v1_professional_list_memberships', methods: ['GET'])]
    #[OA\Get(operationId: 'listOrganisationMemberships', summary: 'List organisation memberships without case content', security: [['professionalSession' => []]], tags: ['Professional access'])]
    public function list(string $id, #[CurrentUser] Professional $actor): JsonResponse
    {
        $organisation = $this->organisation($id);
        $this->requireAdministrator($organisation, $actor);

        return $this->response(['items' => array_map($this->serialize(...), $this->memberships->findByOrganisation($organisation))]);
    }

    #[Route('/api/v1/professional/organisations/{id}/memberships/{professionalId}', name: 'api_v1_professional_grant_membership', methods: ['POST'])]
    #[OA\Post(operationId: 'grantOrganisationMembership', summary: 'Grant a role without assigning any case', security: [['professionalSession' => []]], tags: ['Professional access'])]
    public function grant(string $id, string $professionalId, #[CurrentUser] Professional $actor, #[MapRequestPayload(acceptFormat: 'json')] ManageOrganisationMembershipRequest $payload): JsonResponse
    {
        try {
            $membership = $this->manager->grant($this->organisation($id), $this->professional($professionalId), ProfessionalRole::from($payload->role), $actor, new DateTimeImmutable());
        } catch (LogicException $exception) {
            throw new ProfessionalAccountUnavailableHttpException(previous: $exception);
        }

        return $this->response($this->serialize($membership), 201);
    }

    #[Route('/api/v1/professional/organisations/{id}/memberships/{membershipId}', name: 'api_v1_professional_change_membership', methods: ['PATCH'])]
    #[OA\Patch(operationId: 'changeOrganisationMembership', summary: 'Change, suspend, resume or remove an organisation membership without changing case assignments', security: [['professionalSession' => []]], tags: ['Professional access'])]
    public function change(string $id, string $membershipId, #[CurrentUser] Professional $actor, #[MapRequestPayload(acceptFormat: 'json')] ChangeOrganisationMembershipRequest $payload): JsonResponse
    {
        $membership = $this->membership($this->organisation($id), $membershipId);
        try {
            if ($payload->role !== null) {
                $this->manager->changeRole($membership, ProfessionalRole::from($payload->role), $actor, new DateTimeImmutable());
            } else {
                match ($payload->action) {
                    'suspend' => $this->manager->suspend($membership, $actor, new DateTimeImmutable()),
                    'resume' => $this->manager->resume($membership, $actor, new DateTimeImmutable()),
                    'remove' => $this->manager->remove($membership, $actor, new DateTimeImmutable()),
                    default => throw new LogicException('An organisation membership change is required.'),
                };
            }
        } catch (LogicException $exception) {
            throw new ProfessionalAccountUnavailableHttpException(previous: $exception);
        }

        return $this->response($this->serialize($membership));
    }

    private function organisation(string $id): Organisation
    {
        $organisation = Uuid::isValid($id) ? $this->organisations->findById(Uuid::fromString($id)) : null;
        if (!$organisation instanceof Organisation) throw new ProfessionalAccountUnavailableHttpException();
        return $organisation;
    }

    private function professional(string $id): Professional
    {
        $professional = Uuid::isValid($id) ? $this->professionals->find(Uuid::fromString($id)) : null;
        if (!$professional instanceof Professional) throw new ProfessionalAccountUnavailableHttpException();
        return $professional;
    }

    private function membership(Organisation $organisation, string $id): OrganisationMembership
    {
        $membership = Uuid::isValid($id) ? $this->memberships->findByIdAndOrganisation(Uuid::fromString($id), $organisation) : null;
        if (!$membership instanceof OrganisationMembership) throw new ProfessionalAccountUnavailableHttpException();

        return $membership;
    }

    private function requireAdministrator(Organisation $organisation, Professional $actor): void
    {
        if (!$actor->isActive() || $this->memberships->findActiveByProfessionalAndOrganisation($actor, $organisation, ProfessionalRole::Administrator) === null) throw new ProfessionalAccountUnavailableHttpException();
    }

    /** @return array<string, string|null> */
    private function serialize(OrganisationMembership $membership): array
    {
        return ['id' => $membership->id()->toRfc4122(), 'professionalId' => $membership->professional()->id()->toRfc4122(), 'name' => $membership->professional()->name(), 'role' => $membership->role()->value, 'state' => $membership->revokedAt() !== null ? 'removed' : ($membership->suspendedAt() !== null ? 'suspended' : 'active')];
    }

    /** @param array<string, mixed> $data */
    private function response(array $data, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($data, $status); $response->setCache(['private' => true, 'no_store' => true]); return $response;
    }
}
