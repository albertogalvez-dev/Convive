<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/api/v1/health', name: 'api_v1_health', methods: ['GET'], format: 'json')]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }
}
