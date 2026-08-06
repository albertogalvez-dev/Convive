<?php

declare(strict_types=1);

namespace App\Reporting\Application\VerifyReportAccess;

use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAccessGrantRepository;
use App\Reporting\Domain\ReportAccessSecret;
use App\Reporting\Domain\ReportRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class VerifyReportAccess
{
    public function __construct(
        private ReportRepository $reportRepository,
        private ReportAccessGrantRepository $grantRepository,
    ) {
    }

    public function __invoke(
        VerifyReportAccessCommand $command,
    ): VerifyReportAccessResult {
        try {
            $accessSecret = ReportAccessSecret::fromString(
                $command->accessSecret,
            );
        } catch (InvalidArgumentException) {
            throw ReportAccessDenied::becauseTheSecretWasRejected();
        }

        $report = $this->reportRepository->findByAccessSecret(
            $accessSecret,
        );

        if ($report === null) {
            throw ReportAccessDenied::becauseTheSecretWasRejected();
        }

        $now = DateTimeImmutable::createFromTimestamp(microtime(true));

        $this->revokePreviousGrant($command->previousCapabilityHandle, $now);

        $capability = ReportAccessCapability::generate();
        $grant = ReportAccessGrant::issue($report, $capability, $now);
        $this->grantRepository->save($grant);

        return new VerifyReportAccessResult(
            $capability->reveal(),
            $grant->absoluteExpiresAt(),
        );
    }

    /**
     * Only one anonymous report capability may be active in a browser at a
     * time: unlocking another report must invalidate whatever grant was
     * previously presented, regardless of which report it belonged to.
     */
    private function revokePreviousGrant(
        ?string $previousCapabilityHandle,
        DateTimeImmutable $now,
    ): void {
        if ($previousCapabilityHandle === null) {
            return;
        }

        try {
            $previousCapability = ReportAccessCapability::fromString(
                $previousCapabilityHandle,
            );
        } catch (InvalidArgumentException) {
            return;
        }

        $previousGrant = $this->grantRepository->findByCapability(
            $previousCapability,
        );

        if ($previousGrant === null || $previousGrant->revokedAt() !== null) {
            return;
        }

        $previousGrant->revokeAt($now);
        $this->grantRepository->save($previousGrant);
    }
}
