<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ProfessionalAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'type' => 'https://convive.example/problems/professional-session-required',
            'title' => 'Professional session required',
            'status' => Response::HTTP_UNAUTHORIZED,
            'detail' => 'A valid professional session is required.',
        ], Response::HTTP_UNAUTHORIZED, [
            'Content-Type' => 'application/problem+json',
            'Cache-Control' => 'no-store',
        ]);
    }
}
