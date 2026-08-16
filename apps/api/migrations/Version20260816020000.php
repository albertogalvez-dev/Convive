<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add planned professional absences for operational case continuity (#185).';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('CREATE TABLE professional_absences ('
            .'id UUID NOT NULL, '
            .'professional_id UUID NOT NULL, '
            .'starts_on DATE NOT NULL, '
            .'ends_on DATE NOT NULL, '
            .'note VARCHAR(200) DEFAULT NULL, '
            .'recorded_at TIMESTAMPTZ NOT NULL, '
            .'cancelled_at TIMESTAMPTZ DEFAULT NULL, '
            .'PRIMARY KEY(id), '
            .'CONSTRAINT fk_professional_absence_professional FOREIGN KEY (professional_id) REFERENCES professionals (id), '
            .'CONSTRAINT chk_professional_absence_period CHECK (ends_on >= starts_on))');
        $this->addSql('CREATE INDEX idx_professional_absence_professional_period ON professional_absences (professional_id, starts_on, ends_on)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');

        $this->addSql('DROP TABLE professional_absences');
    }
}
