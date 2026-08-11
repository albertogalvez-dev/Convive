<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditRetentionPolicy;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PurgeExpiredFictionalCaseAuditEvents
{
    public function __construct(
        private CaseAuditEventRepository $events,
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

        return $this->events->purgeBefore(
            $now->sub(new DateInterval(CaseAuditRetentionPolicy::FICTIONAL_RETENTION)),
            $limit,
        );
    }
}
