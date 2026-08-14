<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reviewed, source-versioned task-planning templates (#178)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql(<<<'SQL'
CREATE TABLE case_workflow_task_templates (
    id UUID NOT NULL, source_version_id UUID NOT NULL, stage VARCHAR(40) NOT NULL, kind VARCHAR(30) NOT NULL,
    title VARCHAR(160) NOT NULL, approved BOOLEAN NOT NULL, PRIMARY KEY(id),
    CONSTRAINT fk_case_workflow_template_source FOREIGN KEY (source_version_id) REFERENCES case_workflow_source_versions (id),
    CONSTRAINT chk_case_workflow_template_stage CHECK (stage IN ('identification','immediate_actions','urgent_protection','family_communication','professional_coordination','information_collection','educational_measures','inspection_communication','assessment','action_plan','family_measures','inspection_follow_up')),
    CONSTRAINT chk_case_workflow_template_kind CHECK (kind IN ('internal_action','external_communication')),
    CONSTRAINT chk_case_workflow_template_title CHECK (char_length(btrim(title)) BETWEEN 1 AND 160)
)
SQL);
        $this->addSql("INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved) VALUES ('019ffe70-0000-7000-8000-000000000001','019c4c1d-4fd4-7f6d-a0d1-000000000001','immediate_actions','internal_action','Review the fictional immediate protection plan.',true),('019ffe70-0000-7000-8000-000000000002','019c4c1d-4fd4-7f6d-a0d1-000000000002','information_collection','internal_action','Record the fictional information-gathering step.',true),('019ffe70-0000-7000-8000-000000000003','019c4c1d-4fd4-7f6d-a0d1-000000000004','family_communication','external_communication','Decide whether a fictional family contact is appropriate.',true)");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql('DROP TABLE case_workflow_task_templates');
    }
}
