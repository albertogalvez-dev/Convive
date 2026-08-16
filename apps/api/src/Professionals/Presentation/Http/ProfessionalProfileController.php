<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Application\UpdateProfessionalProfile;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalEmailAlreadyUsed;
use DateTimeImmutable;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class ProfessionalProfileController
{
    public function __construct(
        private UpdateProfessionalProfile $updateProfile,
        private OrganisationMembershipRepository $memberships,
    ) {
    }

    #[Route('/api/v1/professional/profile', name: 'api_v1_professional_get_profile', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getProfessionalProfile',
        summary: 'Get the current professional self-service profile',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The editable profile and its administrator-controlled context.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function show(#[CurrentUser] Professional $professional): JsonResponse
    {
        return $this->json($this->serialize($professional));
    }

    #[Route('/api/v1/professional/profile', name: 'api_v1_professional_update_profile', methods: ['PATCH'])]
    #[OA\Patch(
        operationId: 'updateProfessionalProfile',
        summary: 'Update the current professional name and email',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The updated profile. Changing the email ends every existing session.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'The submitted profile is invalid.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The email address is already in use.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'A professional session is required.'),
        ],
    )]
    public function update(
        #[CurrentUser] Professional $professional,
        #[MapRequestPayload(acceptFormat: 'json')] UpdateProfessionalProfileRequest $payload,
    ): JsonResponse {
        $emailChanged = $professional->email()->toString() !== mb_strtolower(trim($payload->email));

        try {
            $this->updateProfile->update(
                $professional,
                $payload->name,
                ProfessionalEmail::fromString($payload->email),
                DateTimeImmutable::createFromTimestamp(microtime(true)),
            );
        } catch (ProfessionalEmailAlreadyUsed $exception) {
            throw new ProfessionalEmailConflictHttpException(previous: $exception);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The submitted profile is invalid.', $exception);
        }

        return $this->json($this->serialize($professional) + ['sessionEnded' => $emailChanged]);
    }

    /**
     * Role and organisation membership are reported so the interface can show
     * where a setting is administrator-controlled, never so it can be changed
     * here: this controller exposes no route that alters them.
     *
     * @return array<string, mixed>
     */
    private function serialize(Professional $professional): array
    {
        $memberships = [];
        foreach ($this->memberships->findActiveByProfessional($professional) as $membership) {
            $memberships[] = [
                'organisation' => [
                    'id' => $membership->organisation()->id()->toRfc4122(),
                    'name' => $membership->organisation()->name(),
                ],
                'role' => $membership->role()->value,
                'managedByAdministrator' => true,
            ];
        }

        return [
            'id' => $professional->id()->toRfc4122(),
            'name' => $professional->name(),
            'email' => $professional->email()->toString(),
            'memberships' => $memberships,
        ];
    }

    /** @param array<string, mixed> $data */
    private function json(array $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_OK, ['Cache-Control' => 'no-store, private']);
    }
}
