<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Professionals\Domain\Professional;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class ProfessionalSessionController
{
    #[Route('/api/v1/professional/session', name: 'api_v1_professional_login', methods: ['POST'])]
    #[OA\Post(
        operationId: 'createProfessionalSession',
        summary: 'Create a professional session',
        tags: ['Professional access'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'The professional session was created.'),
            new OA\Response(response: 401, description: 'Authentication failed.'),
            new OA\Response(response: 429, description: 'Too many authentication attempts.'),
        ],
    )]
    public function login(
        #[CurrentUser] Professional $professional,
        Request $request,
    ): JsonResponse
    {
        $request->getSession()->migrate(true);

        return $this->sessionResponse($professional);
    }

    #[Route('/api/v1/professional/session', name: 'api_v1_professional_session', methods: ['GET'])]
    #[OA\Get(
        operationId: 'getProfessionalSession',
        summary: 'Get the current professional session',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [
            new OA\Response(response: 200, description: 'The current professional identity.'),
            new OA\Response(response: 401, description: 'No active professional session.'),
        ],
    )]
    public function current(#[CurrentUser] Professional $professional): JsonResponse
    {
        return $this->sessionResponse($professional);
    }

    #[Route('/api/v1/professional/session', name: 'api_v1_professional_logout', methods: ['DELETE'])]
    #[OA\Delete(
        operationId: 'deleteProfessionalSession',
        summary: 'End the current professional session',
        security: [['professionalSession' => []]],
        tags: ['Professional access'],
        responses: [new OA\Response(response: 204, description: 'The session was invalidated.')],
    )]
    public function logout(): never
    {
        throw new \LogicException('The firewall must intercept professional logout.');
    }

    private function sessionResponse(Professional $professional): JsonResponse
    {
        $response = new JsonResponse([
            'professional' => [
                'id' => $professional->id()->toRfc4122(),
                'name' => $professional->name(),
                'email' => $professional->email()->toString(),
            ],
        ]);
        $response->setCache(['private' => true, 'no_store' => true]);

        return $response;
    }
}
