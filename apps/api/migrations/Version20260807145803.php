<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807145803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add professional identities and organisation memberships (#29)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'CREATE TABLE professionals (
                id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_2DBE308EE7927C74
             ON professionals (email)',
        );

        $this->addSql(
            'CREATE TABLE organisation_memberships (
                id UUID NOT NULL,
                professional_id UUID NOT NULL,
                organisation_id UUID NOT NULL,
                role VARCHAR(20) NOT NULL,
                granted_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                revoked_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )',
        );
        $this->addSql(
            'CREATE INDEX IDX_4E05FBE0DB77003
             ON organisation_memberships (professional_id)',
        );
        $this->addSql(
            'CREATE INDEX IDX_4E05FBE09E6B1585
             ON organisation_memberships (organisation_id)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX uniq_organisation_memberships_grant
             ON organisation_memberships (professional_id, organisation_id, role)',
        );
        $this->addSql(
            'ALTER TABLE organisation_memberships
             ADD CONSTRAINT FK_4E05FBE0DB77003
             FOREIGN KEY (professional_id) REFERENCES professionals (id)
             NOT DEFERRABLE',
        );
        $this->addSql(
            'ALTER TABLE organisation_memberships
             ADD CONSTRAINT FK_4E05FBE09E6B1585
             FOREIGN KEY (organisation_id) REFERENCES organisations (id)
             NOT DEFERRABLE',
        );
        $this->addSql(
            'ALTER TABLE organisation_memberships
             ADD CONSTRAINT chk_organisation_memberships_role
             CHECK (role IN (\'triage\', \'administrator\'))',
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(
            'ALTER TABLE organisation_memberships
             DROP CONSTRAINT chk_organisation_memberships_role',
        );
        $this->addSql(
            'ALTER TABLE organisation_memberships
             DROP CONSTRAINT FK_4E05FBE0DB77003',
        );
        $this->addSql(
            'ALTER TABLE organisation_memberships
             DROP CONSTRAINT FK_4E05FBE09E6B1585',
        );
        $this->addSql('DROP TABLE organisation_memberships');
        $this->addSql('DROP TABLE professionals');
    }
}
