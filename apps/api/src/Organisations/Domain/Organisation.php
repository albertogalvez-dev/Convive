<?php

declare(strict_types=1);

namespace App\Organisations\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'organisations')]
class Organisation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(
        name: 'public_reporting_identifier',
        type: 'string',
        length: PublicReportingIdentifier::LENGTH,
        unique: true,
    )]
    private string $publicReportingIdentifier;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        enumType: ReportingChannelStatus::class,
        options: ['default' => 'active'],
    )]
    private ReportingChannelStatus $reportingChannelStatus = ReportingChannelStatus::Active;

    public function __construct(
        Uuid $id,
        string $name,
        PublicReportingIdentifier $publicReportingIdentifier,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->publicReportingIdentifier = $publicReportingIdentifier->toString();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function reportingChannelStatus(): ReportingChannelStatus
    {
        return $this->reportingChannelStatus;
    }

    /** Only an active channel accepts a new report. */
    public function acceptsNewReports(): bool
    {
        return $this->reportingChannelStatus === ReportingChannelStatus::Active;
    }

    public function pauseReportingChannel(): void
    {
        if ($this->reportingChannelStatus === ReportingChannelStatus::Retired) {
            throw new LogicException('A retired reporting channel cannot be paused.');
        }

        $this->reportingChannelStatus = ReportingChannelStatus::Paused;
    }

    public function activateReportingChannel(): void
    {
        if ($this->reportingChannelStatus === ReportingChannelStatus::Retired) {
            throw new LogicException('A retired reporting channel cannot be reactivated.');
        }

        $this->reportingChannelStatus = ReportingChannelStatus::Active;
    }

    public function retireReportingChannel(): void
    {
        $this->reportingChannelStatus = ReportingChannelStatus::Retired;
    }

    /**
     * Issues a fresh identifier, which stops the previous link resolving at
     * all. A reporter who already holds an access code is unaffected: the code
     * authorises the report directly through
     * `/api/v1/public/report-access-grants`, which never takes a centre
     * identifier, so rotation changes routing for new reports only and grants
     * or removes no report access.
     */
    public function rotateReportingIdentifier(PublicReportingIdentifier $replacement): void
    {
        $this->publicReportingIdentifier = $replacement->toString();
        $this->reportingChannelStatus = ReportingChannelStatus::Active;
    }

    public function publicReportingIdentifier(): PublicReportingIdentifier
    {
        return PublicReportingIdentifier::fromString(
            $this->publicReportingIdentifier,
        );
    }
}
