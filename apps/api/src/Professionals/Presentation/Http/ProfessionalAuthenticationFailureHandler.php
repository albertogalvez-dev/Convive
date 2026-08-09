<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class ProfessionalAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private SecurityEventLogger $securityEventLogger)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->securityEventLogger->professionalAuthenticationFailed($request);

        $rateLimited = $exception instanceof TooManyLoginAttemptsAuthenticationException;
        $status = $rateLimited ? Response::HTTP_TOO_MANY_REQUESTS : Response::HTTP_UNAUTHORIZED;

        return new JsonResponse([
            'type' => $rateLimited
                ? 'https://convive.example/problems/too-many-authentication-attempts'
                : 'https://convive.example/problems/authentication-failed',
            'title' => $rateLimited ? 'Too many authentication attempts' : 'Authentication failed',
            'status' => $status,
            'detail' => $rateLimited
                ? 'Too many authentication attempts were made. Try again later.'
                : 'The supplied credentials could not be authenticated.',
        ], $status, [
            'Content-Type' => 'application/problem+json',
            'Cache-Control' => 'no-store',
        ]);
    }
}
