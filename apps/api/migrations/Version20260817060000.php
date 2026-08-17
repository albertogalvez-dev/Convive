<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Aragón as a second territorial protocol profile and organisation territorial scope (#254)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // An organisation's territorial scope is never inferred and never
        // defaults to a real region: NULL until an administrator explicitly
        // assigns one (see Organisation::assignTerritorialScope()).
        $this->addSql('ALTER TABLE organisations ADD territorial_scope VARCHAR(20) DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE organisations ADD CONSTRAINT chk_organisation_territorial_scope '
            . 'CHECK (territorial_scope IS NULL OR char_length(btrim(territorial_scope)) BETWEEN 1 AND 20)',
        );

        // Source verified directly against the Boletín Oficial de Aragón, not
        // a secondary aggregator: ORDEN ECD/584/2026, de 13 de abril de 2026,
        // published in BOA nº 78 (27 April 2026), amending Orden ECD/1003/2018.
        // https://www.boa.aragon.es/cgi-bin/EBOA/BRSCGI?CMD=VEROBJ&MLKOB=1444982450505
        //
        // Its full text carries NO explicit hour/day deadlines for incident
        // response -- only a qualitative "activación inmediata" (immediate
        // activation) obligation on any reasonable indication (art. 12.4) and
        // a 6-month deadline for existing protocols to be administratively
        // adapted to this order (disposición adicional única), which is not
        // an incident-response deadline. A prior, unverified research pass
        // claimed specific 24h/10-day figures for Aragón; that claim does not
        // appear anywhere in the actual order and is not modelled here.
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c1e-4fd4-7f6d-a0d1-000000000001',
        'ES-AR-ORDER-2026-04-13-ARTS-12-13',
        'BOA-78-2026',
        'Aragonese school bullying protocol (Orden ECD/584/2026)',
        'https://www.boa.aragon.es/cgi-bin/EBOA/BRSCGI?CMD=VEROBJ&MLKOB=1444982450505',
        'ES-AR',
        'binding',
        '2026-04-27',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe71-0000-7000-8000-000000000001',
        '019c4c1e-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Review the fictional immediate protocol activation.',
        true
    ),
    (
        '019ffe71-0000-7000-8000-000000000002',
        '019c4c1e-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional victim-protection measures are in place.',
        true
    ),
    (
        '019ffe71-0000-7000-8000-000000000003',
        '019c4c1e-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Record the fictional rigorous, objective investigation.',
        true
    ),
    (
        '019ffe71-0000-7000-8000-000000000004',
        '019c4c1e-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional immediate family communication.',
        true
    )
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c1e-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c1e-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql('ALTER TABLE organisations DROP CONSTRAINT chk_organisation_territorial_scope');
        $this->addSql('ALTER TABLE organisations DROP territorial_scope');
    }
}
