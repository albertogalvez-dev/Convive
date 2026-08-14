<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_workflow_task_templates')]
class WorkflowTaskTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: WorkflowSourceVersion::class)]
    #[ORM\JoinColumn(name: 'source_version_id', referencedColumnName: 'id', nullable: false)]
    private WorkflowSourceVersion $source;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: CaseProtocolStage::class)]
    private CaseProtocolStage $stage;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: CaseTaskKind::class)]
    private CaseTaskKind $kind;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $title;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $approved;

    public function __construct(Uuid $id, WorkflowSourceVersion $source, CaseProtocolStage $stage, CaseTaskKind $kind, string $title, bool $approved = true)
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 160) {
            throw new InvalidArgumentException('A workflow task template title must contain between 1 and 160 characters.');
        }

        $this->id = $id;
        $this->source = $source;
        $this->stage = $stage;
        $this->kind = $kind;
        $this->title = $title;
        $this->approved = $approved;
    }

    public function id(): Uuid { return $this->id; }
    public function source(): WorkflowSourceVersion { return $this->source; }
    public function stage(): CaseProtocolStage { return $this->stage; }
    public function kind(): CaseTaskKind { return $this->kind; }
    public function title(): string { return $this->title; }
    public function isApproved(): bool { return $this->approved; }
}
