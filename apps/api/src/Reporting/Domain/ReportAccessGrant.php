<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'report_access_grants')]
class ReportAccessGrant
{
    public const IDLE_TIMEOUT = 'PT15M';
    public const ABSOLUTE_LIFETIME = 'PT2H';
    public const ACTIVITY_PERSIST_INTERVAL = 'PT1M';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Report::class)]
    #[ORM\JoinColumn(
        name: 'report_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Report $report;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        unique: true,
    )]
    private string $capabilityHash;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $issuedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $lastUsedAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $absoluteExpiresAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    private function __construct(
        Uuid $id,
        Report $report,
        string $capabilityHash,
        DateTimeImmutable $issuedAt,
    ) {
        $this->id = $id;
        $this->report = $report;
        $this->capabilityHash = $capabilityHash;
        $this->issuedAt = $issuedAt;
        $this->lastUsedAt = $issuedAt;
        $this->absoluteExpiresAt = $issuedAt->add(
            new DateInterval(self::ABSOLUTE_LIFETIME),
        );
    }

    public static function issue(
        Report $report,
        ReportAccessCapability $capability,
        DateTimeImmutable $issuedAt,
    ): self {
        return new self(
            Uuid::v7(),
            $report,
            $capability->lookupHash(),
            $issuedAt,
        );
    }

    public function isValidAt(DateTimeImmutable $now): bool
    {
        if ($this->revokedAt !== null) {
            return false;
        }

        if ($now > $this->absoluteExpiresAt) {
            return false;
        }

        $idleDeadline = $this->lastUsedAt->add(
            new DateInterval(self::IDLE_TIMEOUT),
        );

        return $now <= $idleDeadline;
    }

    public function recordUseAt(DateTimeImmutable $now): bool
    {
        if ($now < $this->lastUsedAt->add(
            new DateInterval(self::ACTIVITY_PERSIST_INTERVAL),
        )) {
            return false;
        }

        $this->lastUsedAt = $now;

        return true;
    }

    public function revokeAt(DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function report(): Report
    {
        return $this->report;
    }

    public function issuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function lastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function absoluteExpiresAt(): DateTimeImmutable
    {
        return $this->absoluteExpiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
}
