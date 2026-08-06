<?php

declare(strict_types=1);

namespace App\Reporting\Application\RevokeReportAccess;

use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrantRepository;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Idempotent by design: revoking an unknown, already-revoked or malformed
 * capability handle is a safe no-op, so a caller can always clear its
 * cookie without needing to know whether a grant still exists.
 */
final readonly class RevokeReportAccess
{
    public function __construct(
        private ReportAccessGrantRepository $grantRepository,
    ) {
    }

    public function __invoke(RevokeReportAccessCommand $command): void
    {
        try {
            $capability = ReportAccessCapability::fromString(
                $command->capabilityHandle,
            );
        } catch (InvalidArgumentException) {
            return;
        }

        $grant = $this->grantRepository->findByCapability($capability);

        if ($grant === null) {
            return;
        }

        $grant->revokeAt(
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );
        $this->grantRepository->save($grant);
    }
}
