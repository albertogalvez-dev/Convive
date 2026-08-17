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

    /**
     * Which territorial protocol profile (#253) this organisation's
     * professionals see in their task-planning catalogue -- null until an
     * administrator explicitly assigns one. Never inferred from the
     * organisation's name, address or anything else: an unscoped
     * organisation sees no territorial templates at all, not a default
     * region's, per the standing rule that no organisation is silently
     * re-scoped.
     */
    #[ORM\Column(name: 'territorial_scope', type: Types::STRING, length: 20, nullable: true)]
    private ?string $territorialScope = null;

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

    public function territorialScope(): ?string
    {
        return $this->territorialScope;
    }

    /**
     * The explicit administrative action that assigns (or reassigns) an
     * organisation's territorial protocol profile. There is no automatic or
     * inferred assignment anywhere else in this class.
     */
    public function assignTerritorialScope(string $territorialScope): void
    {
        $territorialScope = trim($territorialScope);

        if ($territorialScope === '' || mb_strlen($territorialScope) > 20) {
            throw new LogicException('A territorial scope must contain between 1 and 20 characters.');
        }

        $this->territorialScope = $territorialScope;
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
