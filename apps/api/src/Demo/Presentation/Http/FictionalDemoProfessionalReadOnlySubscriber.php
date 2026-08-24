<?php

declare(strict_types=1);

namespace App\Demo\Presentation\Http;

use App\Demo\Application\FictionalDemoProfessionalSession;
use App\Shared\Infrastructure\Logging\SecurityEventLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class FictionalDemoProfessionalReadOnlySubscriber implements EventSubscriberInterface
{
    public function __construct(private SecurityEventLogger $securityEventLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['denyProfessionalWrites', 17]];
    }

    public function denyProfessionalWrites(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (
            $request->isMethodSafe()
            || !str_starts_with($request->getPathInfo(), '/api/v1/professional/')
            || $request->attributes->getString('_route') === 'api_v1_professional_logout'
        ) {
            return;
        }

        if (FictionalDemoProfessionalSession::role($request) === null) {
            return;
        }

        $this->securityEventLogger->fictionalDemoProfessionalWriteDenied($request);
        throw new AccessDeniedHttpException('The fictional professional demonstration is read-only.');
    }
}
