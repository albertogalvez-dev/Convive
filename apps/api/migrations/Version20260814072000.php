<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814072000 extends AbstractMigration
{
    public function getDescription(): string { return 'Add minimised in-product professional notifications and optional preferences (#182)'; }
    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql("CREATE TABLE professional_notifications (id UUID NOT NULL, recipient_professional_id UUID NOT NULL, case_id UUID NOT NULL, type VARCHAR(30) NOT NULL, created_at TIMESTAMPTZ NOT NULL, read_at TIMESTAMPTZ DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT fk_professional_notification_recipient FOREIGN KEY (recipient_professional_id) REFERENCES professionals (id), CONSTRAINT fk_professional_notification_case FOREIGN KEY (case_id) REFERENCES managed_cases (id), CONSTRAINT chk_professional_notification_type CHECK (type IN ('case_assigned', 'case_lifecycle_changed')))");
        $this->addSql('CREATE INDEX idx_professional_notification_recipient_created ON professional_notifications (recipient_professional_id, created_at, id)');
        $this->addSql("CREATE TABLE professional_notification_preferences (professional_id UUID NOT NULL, notification_type VARCHAR(30) NOT NULL, enabled BOOLEAN NOT NULL, PRIMARY KEY(professional_id, notification_type), CONSTRAINT fk_professional_notification_preference_professional FOREIGN KEY (professional_id) REFERENCES professionals (id), CONSTRAINT chk_professional_notification_preference_type CHECK (notification_type IN ('case_lifecycle_changed')))");
        $this->addSql('CREATE UNIQUE INDEX uniq_professional_notification_preference ON professional_notification_preferences (professional_id, notification_type)');
    }
    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration can only be executed safely on PostgreSQL.');
        $this->addSql('DROP TABLE professional_notification_preferences');
        $this->addSql('DROP TABLE professional_notifications');
    }
}
