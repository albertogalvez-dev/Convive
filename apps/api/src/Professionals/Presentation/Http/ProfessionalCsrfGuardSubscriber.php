<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class ProfessionalCsrfGuardSubscriber implements EventSubscriberInterface
{
    private const TOKEN_IDS = [
        'api_v1_professional_login' => 'professional_login',
        'api_v1_professional_logout' => 'professional_logout',
        'api_v1_professional_review_report' => 'professional_report_review',
        'api_v1_professional_triage_report' => 'professional_report_triage',
        'api_v1_professional_respond_to_report' => 'professional_report_response',
    ];

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SecurityEventLogger $securityEventLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['validate', 16]];
    }

    public function validate(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $tokenId = self::TOKEN_IDS[$request->attributes->getString('_route')] ?? null;

        if (null === $tokenId) {
            return;
        }

        $submittedToken = $request->headers->get('X-Csrf-Token')
            ?? $this->csrfTokenManager->getToken($tokenId)->getValue();

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $submittedToken))) {
            $this->securityEventLogger->csrfDenied($request);
            throw new AccessDeniedHttpException('The request failed CSRF validation.');
        }
    }
}
