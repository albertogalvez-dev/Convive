<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditRetentionPolicy;
use App\Cases\Domain\ProfessionalExportEventRepository;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PurgeExpiredFictionalCaseAuditEvents
{
    public function __construct(
        private CaseAuditEventRepository $events,
        private ProfessionalExportEventRepository $professionalExportEvents,
        #[Autowire('%env(bool:APP_DEMO_MODE)%')]
        private bool $fictionalDemoMode,
    ) {
    }

    public function __invoke(int $limit, DateTimeImmutable $now): int
    {
        if (!$this->fictionalDemoMode) {
            throw new LogicException('Case audit retention is available only in explicit fictional demo mode.');
        }

        if ($limit < 1 || $limit > CaseAuditRetentionPolicy::MAX_PURGE_BATCH) {
            throw new InvalidArgumentException('The case audit cleanup limit is invalid.');
        }

        $cutoff = $now->sub(new DateInterval(CaseAuditRetentionPolicy::FICTIONAL_RETENTION));
        $caseEvents = $this->events->purgeBefore($cutoff, $limit);
        if ($caseEvents === $limit) {
            return $caseEvents;
        }

        return $caseEvents + $this->professionalExportEvents->purgeBefore(
            $cutoff,
            $limit - $caseEvents,
        );
    }
}
