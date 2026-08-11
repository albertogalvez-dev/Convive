<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Application\PublicReportingModePolicy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class PublicReportingMutationBoundarySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PublicReportingModePolicy $modePolicy,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['rejectReporterMutationOutsideOperationalMode', 32],
        ];
    }

    public function rejectReporterMutationOutsideOperationalMode(
        RequestEvent $event,
    ): void {
        if (
            !$event->isMainRequest()
            || $this->modePolicy->acceptsReporterMutations()
            || !in_array($event->getRequest()->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || !$this->isReporterMutationPath($event->getRequest()->getPathInfo())
        ) {
            return;
        }

        throw new PublicReportingUnavailableHttpException();
    }

    private function isReporterMutationPath(string $path): bool
    {
        if (
            str_starts_with($path, '/api/v1/reporter/')
            || $path === '/api/v1/public/report-access-grants'
            || $path === '/api/v1/public/reporter-email-verifications'
        ) {
            return true;
        }

        return preg_match(
            '#^/api/v1/public/organisations/[^/]+/reports$#',
            $path,
        ) === 1;
    }
}
