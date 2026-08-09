<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ProfessionalSessionLifetimeSubscriber implements EventSubscriberInterface
{
    private const MAX_IDLE_SECONDS = 1800;
    private const MAX_ABSOLUTE_SECONDS = 43200;

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['expireStaleSession', 9]];
    }

    public function expireStaleSession(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getRequest()->hasPreviousSession()) {
            return;
        }

        $session = $event->getRequest()->getSession();
        $session->start();
        $metadata = $session->getMetadataBag();
        $now = time();

        if (
            $now - $metadata->getLastUsed() > self::MAX_IDLE_SECONDS
            || $now - $metadata->getCreated() > self::MAX_ABSOLUTE_SECONDS
        ) {
            $session->invalidate();
        }
    }
}
