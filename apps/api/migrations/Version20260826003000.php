<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use Spanish source titles for the fictional Spanish professional demo (#456)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
UPDATE case_workflow_source_versions
SET title = CASE code
    WHEN 'ES-AN-ORDER-2011-06-20-ANNEX-I' THEN 'Protocolo andaluz de actuación ante el acoso escolar'
    WHEN 'ES-AN-INSTRUCTIONS-2017-01-11-CYBERBULLYING' THEN 'Instrucciones andaluzas sobre ciberacoso'
    WHEN 'ES-MEFPD-FRAMEWORK-2026-04-15' THEN 'Marco estatal de referencia contra el acoso y ciberacoso'
    WHEN 'CONVIVE-INTERNAL-ANDALUSIA-DEMO' THEN 'Objetivo interno ficticio de Convive para Andalucía'
    ELSE title
END
WHERE code IN (
    'ES-AN-ORDER-2011-06-20-ANNEX-I',
    'ES-AN-INSTRUCTIONS-2017-01-11-CYBERBULLYING',
    'ES-MEFPD-FRAMEWORK-2026-04-15',
    'CONVIVE-INTERNAL-ANDALUSIA-DEMO'
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
UPDATE case_workflow_source_versions
SET title = CASE code
    WHEN 'ES-AN-ORDER-2011-06-20-ANNEX-I' THEN 'Andalusian school bullying protocol'
    WHEN 'ES-AN-INSTRUCTIONS-2017-01-11-CYBERBULLYING' THEN 'Andalusian cyberbullying instructions'
    WHEN 'ES-MEFPD-FRAMEWORK-2026-04-15' THEN 'National bullying and cyberbullying reference framework'
    WHEN 'CONVIVE-INTERNAL-ANDALUSIA-DEMO' THEN 'Convive fictional Andalusian demonstration target'
    ELSE title
END
WHERE code IN (
    'ES-AN-ORDER-2011-06-20-ANNEX-I',
    'ES-AN-INSTRUCTIONS-2017-01-11-CYBERBULLYING',
    'ES-MEFPD-FRAMEWORK-2026-04-15',
    'CONVIVE-INTERNAL-ANDALUSIA-DEMO'
)
SQL);
    }
}
