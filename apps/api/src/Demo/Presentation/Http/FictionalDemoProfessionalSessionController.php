<?php

declare(strict_types=1);

namespace App\Demo\Presentation\Http;

use App\Demo\Application\FictionalDemoProfessionalSession;
use App\Demo\Domain\FictionalDemoDataset;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class FictionalDemoProfessionalSessionController
{
    public function __construct(
        private ProfessionalRepository $professionals,
        private Security $security,
        #[Autowire('%env(bool:APP_DEMO_MODE)%')]
        private bool $demoMode,
    ) {
    }

    #[Route('/api/v1/demo/professional-session', name: 'api_v1_demo_professional_session', methods: ['POST'])]
    #[OA\Post(
        operationId: 'createFictionalDemoProfessionalSession',
        summary: 'Create a read-only fictional professional demonstration session',
        tags: ['Fictional demonstration'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                additionalProperties: false,
                required: ['role'],
                properties: [new OA\Property(property: 'role', type: 'string', enum: ['triage', 'administrator', 'case_lead', 'case_contributor', 'case_observer'])],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'The read-only fictional session was created.'),
            new OA\Response(response: 404, description: 'The fictional demonstration is unavailable.'),
            new OA\Response(response: 422, description: 'The requested demonstration role is invalid.'),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        if (!$this->demoMode) {
            throw new NotFoundHttpException();
        }

        $payload = $request->toArray();
        $role = $payload['role'] ?? null;
        $email = match ($role) {
            FictionalDemoProfessionalSession::TRIAGE => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
            FictionalDemoProfessionalSession::ADMINISTRATOR => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL,
            FictionalDemoProfessionalSession::CASE_LEAD => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_EMAIL,
            FictionalDemoProfessionalSession::CASE_CONTRIBUTOR => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_EMAIL,
            FictionalDemoProfessionalSession::CASE_OBSERVER => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_EMAIL,
            default => throw new UnprocessableEntityHttpException('The demonstration role is invalid.'),
        };
        $professional = $this->professionals->findByEmail(ProfessionalEmail::fromString($email));

        if ($professional === null) {
            throw new NotFoundHttpException();
        }

        $session = $request->getSession();
        $session->migrate(true);
        $this->security->login($professional, 'json_login', 'main');
        $session->set(FictionalDemoProfessionalSession::ROLE_KEY, $role);

        $response = new JsonResponse([
            'professional' => [
                'id' => $professional->id()->toRfc4122(),
                'name' => $professional->name(),
                'email' => $professional->email()->toString(),
            ],
            'demonstrationRole' => $role,
        ]);
        $response->setCache(['private' => true, 'no_store' => true]);

        return $response;
    }
}
