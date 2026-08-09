<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'report_follow_up_entries')]
class ReportFollowUpEntry
{
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
        length: 20,
        enumType: FollowUpAuthorType::class,
    )]
    private FollowUpAuthorType $authorType;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $professionalAuthorId;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Report $report,
        FollowUpAuthorType $authorType,
        ?Uuid $professionalAuthorId,
        FollowUpEntryContent $content,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->report = $report;
        $this->authorType = $authorType;
        $this->professionalAuthorId = $professionalAuthorId;
        $this->content = $content->toString();
        $this->createdAt = $createdAt;
    }

    public static function addedByReporter(
        Report $report,
        FollowUpEntryContent $content,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            Uuid::v7(),
            $report,
            FollowUpAuthorType::Reporter,
            null,
            $content,
            $createdAt,
        );
    }

    public static function addedByProfessional(
        Report $report,
        Uuid $professionalAuthorId,
        FollowUpEntryContent $content,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            Uuid::v7(),
            $report,
            FollowUpAuthorType::Professional,
            $professionalAuthorId,
            $content,
            $createdAt,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function report(): Report
    {
        return $this->report;
    }

    public function authorType(): FollowUpAuthorType
    {
        return $this->authorType;
    }

    public function content(): FollowUpEntryContent
    {
        return FollowUpEntryContent::fromString($this->content);
    }

    public function professionalAuthorId(): ?Uuid
    {
        return $this->professionalAuthorId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
