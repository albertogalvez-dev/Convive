<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Professionals\Application\ManageProfessionalAccount;
use App\Professionals\Application\ProfessionalCredentialResult;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalEmailAlreadyUsed;
use App\Professionals\Domain\ProfessionalRepository;
use App\Professionals\Domain\ProfessionalRole;
use App\Shared\Presentation\Http\RateLimitEnforcer;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

final readonly class ProfessionalAccountController
{
    public function __construct(
        private OrganisationRepository $organisations,
        private OrganisationMembershipRepository $memberships,
        private ProfessionalRepository $professionals,
        private ManageProfessionalAccount $accounts,
        private RateLimitEnforcer $rateLimitEnforcer,
        #[Autowire(service: 'limiter.professional_credential_acceptance')]
        private RateLimiterFactory $credentialAcceptanceLimiter,
        #[Autowire(service: 'limiter.professional_password_reset')]
        private RateLimiterFactory $passwordResetLimiter,
    ) {
    }

    #[Route('/api/v1/professional/account-credentials/accept', name: 'api_v1_professional_accept_credential', methods: ['POST'])]
    #[OA\Post(
        operationId: 'acceptProfessionalCredential',
        summary: 'Activate a professional account or set a replacement password',
        tags: ['Professional access'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AcceptProfessionalCredentialRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'The one-time credential was accepted.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The credential is unavailable.'),
            new OA\Response(response: Response::HTTP_TOO_MANY_REQUESTS, description: 'Too many credential attempts were made.'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'The credential request is invalid.'),
        ],
    )]
    public function acceptCredential(
        Request $request,
        #[MapRequestPayload(acceptFormat: 'json')] AcceptProfessionalCredentialRequest $payload,
    ): Response
    {
        $this->rateLimitEnforcer->enforce(
            $this->credentialAcceptanceLimiter,
            'professional_credential_acceptance',
            $request,
            $payload->secret,
        );
        if (!$this->accounts->acceptCredential($payload->secret, $payload->password, new DateTimeImmutable())) {
            throw new ProfessionalAccountUnavailableHttpException();
        }

        return new Response(status: Response::HTTP_NO_CONTENT, headers: ['Cache-Control' => 'no-store, private']);
    }

    #[Route('/api/v1/professional/account-administration', name: 'api_v1_professional_account_administration', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listProfessionalAccountAdministrations',
        summary: 'List organisations whose professional accounts the current user administers',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'The administered organisation list.')],
    )]
    public function administrations(#[CurrentUser] Professional $actor): JsonResponse
    {
        $items = [];
        foreach ($this->memberships->findActiveByProfessional($actor) as $membership) {
            if ($membership->role() === ProfessionalRole::Administrator) {
                $organisation = $membership->organisation();
                $items[$organisation->id()->toRfc4122()] = [
                    'id' => $organisation->id()->toRfc4122(),
                    'name' => $organisation->name(),
                ];
            }
        }
        uasort($items, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return $this->noStore(['items' => array_values($items)]);
    }

    #[Route('/api/v1/professional/organisations/{id}/accounts', name: 'api_v1_professional_list_accounts', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listOrganisationProfessionalAccounts',
        summary: 'List professional accounts in an administered organisation',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'The account lifecycle list.')],
    )]
    public function list(string $id, #[CurrentUser] Professional $actor): JsonResponse
    {
        $organisation = $this->organisation($id);
        $this->requireAdministrator($organisation, $actor);
        $items = [];
        foreach ($this->memberships->findActiveByOrganisation($organisation) as $membership) {
            $professional = $membership->professional();
            $items[$professional->id()->toRfc4122()] = $this->serializeProfessional($professional, $membership->role());
        }
        uasort($items, static fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return $this->noStore(['items' => array_values($items)]);
    }

    #[Route('/api/v1/professional/organisations/{id}/accounts', name: 'api_v1_professional_invite_account', methods: ['POST'])]
    #[OA\Post(
        operationId: 'inviteOrganisationProfessional',
        summary: 'Create a fictional professional account and one-time activation credential',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/InviteProfessionalRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'The account and activation credential were created.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The organisation is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The account request conflicts with existing lifecycle state.'),
        ],
    )]
    public function invite(
        string $id,
        #[CurrentUser] Professional $actor,
        #[MapRequestPayload(acceptFormat: 'json')] InviteProfessionalRequest $payload,
    ): JsonResponse {
        $organisation = $this->organisation($id);
        try {
            $result = $this->accounts->invite($organisation, $payload->name, ProfessionalEmail::fromString($payload->email), ProfessionalRole::from($payload->role), $actor, new DateTimeImmutable());
        } catch (InvalidArgumentException|LogicException $exception) {
            throw new ProfessionalAccountUnavailableHttpException(previous: $exception);
        }

        return $this->noStore($this->serializeCredential($result), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/professional/organisations/{id}/accounts/{professionalId}/password-reset', name: 'api_v1_professional_reset_account_password', methods: ['POST'])]
    #[OA\Post(
        operationId: 'issueProfessionalPasswordReset',
        summary: 'Issue a one-time local password-reset credential',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'The reset credential was created.'),
            new OA\Response(response: Response::HTTP_TOO_MANY_REQUESTS, description: 'Too many reset credentials were issued.'),
        ],
    )]
    public function resetPassword(
        string $id,
        string $professionalId,
        Request $request,
        #[CurrentUser] Professional $actor,
    ): JsonResponse
    {
        $organisation = $this->organisation($id);
        $target = $this->professional($professionalId);
        $this->rateLimitEnforcer->enforce(
            $this->passwordResetLimiter,
            'professional_password_reset',
            $request,
            $actor->id()->toRfc4122(),
        );
        try {
            $result = $this->accounts->issuePasswordReset($organisation, $target, $actor, new DateTimeImmutable());
        } catch (LogicException $exception) {
            throw new ProfessionalAccountUnavailableHttpException(previous: $exception);
        }

        return $this->noStore($this->serializeCredential($result), Response::HTTP_CREATED);
    }

    #[Route('/api/v1/professional/organisations/{id}/accounts/{professionalId}/status', name: 'api_v1_professional_change_account_status', methods: ['PATCH'])]
    #[OA\Patch(
        operationId: 'changeProfessionalAccountStatus',
        summary: 'Suspend, reactivate or deactivate a professional account',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ChangeProfessionalAccountStatusRequest')),
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'The lifecycle state was changed.')],
    )]
    public function changeStatus(
        string $id,
        string $professionalId,
        #[CurrentUser] Professional $actor,
        #[MapRequestPayload(acceptFormat: 'json')] ChangeProfessionalAccountStatusRequest $payload,
    ): JsonResponse {
        $organisation = $this->organisation($id);
        $target = $this->professional($professionalId);
        try {
            match ($payload->action) {
                'suspend' => $this->accounts->suspend($organisation, $target, $actor),
                'reactivate' => $this->accounts->reactivate($organisation, $target, $actor),
                'deactivate' => $this->accounts->deactivate($organisation, $target, $actor),
                default => throw new LogicException('Unsupported professional account lifecycle action.'),
            };
        } catch (LogicException $exception) {
            throw new ProfessionalAccountUnavailableHttpException(previous: $exception);
        }

        return $this->noStore($this->serializeProfessional($target));
    }

    #[Route('/api/v1/professional/organisations/{id}/accounts/{professionalId}/email', name: 'api_v1_professional_correct_account_email', methods: ['PATCH'])]
    #[OA\Patch(
        operationId: 'correctProfessionalAccountEmail',
        summary: "Correct a professional's login email address",
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CorrectProfessionalEmailRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'The corrected account. Changing the email ends every session the professional held.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'The submitted address is invalid.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'The organisation or professional is unavailable in this scope.'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'The email address is already in use.'),
        ],
    )]
    public function correctEmail(
        string $id,
        string $professionalId,
        #[CurrentUser] Professional $actor,
        #[MapRequestPayload(acceptFormat: 'json')] CorrectProfessionalEmailRequest $payload,
    ): JsonResponse {
        $organisation = $this->organisation($id);
        $target = $this->professional($professionalId);

        try {
            $email = ProfessionalEmail::fromString($payload->email);
            $sessionEnded = !$target->email()->equals($email);
            $this->accounts->correctEmail($organisation, $target, $email, $actor, new DateTimeImmutable());
        } catch (ProfessionalEmailAlreadyUsed $exception) {
            throw new ProfessionalEmailConflictHttpException(previous: $exception);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The submitted address is invalid.', $exception);
        } catch (LogicException $exception) {
            throw new ProfessionalAccountUnavailableHttpException(previous: $exception);
        }

        return $this->noStore($this->serializeProfessional($target) + ['sessionEnded' => $sessionEnded]);
    }

    private function organisation(string $id): Organisation
    {
        $organisation = Uuid::isValid($id) ? $this->organisations->findById(Uuid::fromString($id)) : null;
        if (!$organisation instanceof Organisation) {
            throw new ProfessionalAccountUnavailableHttpException();
        }

        return $organisation;
    }

    private function professional(string $id): Professional
    {
        $professional = Uuid::isValid($id) ? $this->professionals->find(Uuid::fromString($id)) : null;
        if (!$professional instanceof Professional) {
            throw new ProfessionalAccountUnavailableHttpException();
        }

        return $professional;
    }

    private function requireAdministrator(Organisation $organisation, Professional $actor): void
    {
        if (!$actor->isActive() || $this->memberships->findActiveByProfessionalAndOrganisation($actor, $organisation, ProfessionalRole::Administrator) === null) {
            throw new ProfessionalAccountUnavailableHttpException();
        }
    }

    /** @return array{id: string, name: string, email: string, status: string, role?: string} */
    private function serializeProfessional(Professional $professional, ?ProfessionalRole $role = null): array
    {
        $data = [
            'id' => $professional->id()->toRfc4122(),
            'name' => $professional->name(),
            'email' => $professional->email()->toString(),
            'status' => $professional->accountStatus()->value,
        ];
        if ($role !== null) {
            $data['role'] = $role->value;
        }

        return $data;
    }

    /** @return array{professional: array{id: string, name: string, email: string, status: string}, credential: array{secret: string, expiresAt: string}} */
    private function serializeCredential(ProfessionalCredentialResult $result): array
    {
        return [
            'professional' => $this->serializeProfessional($result->professional),
            'credential' => [
                'secret' => $result->secret,
                'expiresAt' => $result->expiresAt->format(DATE_ATOM),
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    private function noStore(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->setCache(['private' => true, 'no_store' => true]);

        return $response;
    }
}
