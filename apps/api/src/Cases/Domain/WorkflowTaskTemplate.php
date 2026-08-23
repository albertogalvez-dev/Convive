<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_workflow_task_templates')]
#[ORM\Index(name: 'idx_case_workflow_template_source', columns: ['source_version_id'])]
#[ORM\UniqueConstraint(name: 'uniq_case_workflow_task_template_title_key', columns: ['title_key'])]
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

    /**
     * Stable key a client resolves to show this step in the reader's own
     * language. The title above stays the Spanish source: where no
     * translation exists for a locale, a client falls back to it rather than
     * showing a raw key.
     */
    #[ORM\Column(name: 'title_key', type: Types::STRING, length: 160)]
    private string $titleKey;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $approved;

    public function __construct(Uuid $id, WorkflowSourceVersion $source, CaseProtocolStage $stage, CaseTaskKind $kind, string $title, bool $approved = true, ?string $titleKey = null)
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 160) {
            throw new InvalidArgumentException('A workflow task template title must contain between 1 and 160 characters.');
        }

        $titleKey = trim($titleKey ?? self::deriveTitleKey($source, $stage));
        if ($titleKey === '' || mb_strlen($titleKey) > 160) {
            throw new InvalidArgumentException('A workflow task template title key must contain between 1 and 160 characters.');
        }

        $this->id = $id;
        $this->source = $source;
        $this->stage = $stage;
        $this->kind = $kind;
        $this->title = $title;
        $this->titleKey = $titleKey;
        $this->approved = $approved;
    }

    /**
     * Derived rather than supplied, because (territory, stage) is unique
     * across every template. A new territorial profile therefore gets its
     * keys for free and cannot forget one.
     */
    public static function deriveTitleKey(WorkflowSourceVersion $source, CaseProtocolStage $stage): string
    {
        $territory = strtolower(str_replace('-', '_', $source->territory()));

        return sprintf('caseWorkflow.template.%s.%s', $territory, $stage->value);
    }

    public function id(): Uuid { return $this->id; }
    public function source(): WorkflowSourceVersion { return $this->source; }
    public function stage(): CaseProtocolStage { return $this->stage; }
    public function kind(): CaseTaskKind { return $this->kind; }
    public function title(): string { return $this->title; }
    public function titleKey(): string { return $this->titleKey; }
    public function isApproved(): bool { return $this->approved; }
}
