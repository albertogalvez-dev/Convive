<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802132349 extends AbstractMigration
{
    private const PREFIX = 'ORG_';
    private const PAYLOAD_LENGTH = 16;
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function getDescription(): string
    {
        return 'Add a unique public reporting identifier to organisations';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection
                    ->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $organisationIds = $this->connection->fetchFirstColumn(
            'SELECT id::text FROM organisations ORDER BY id',
        );

        $this->addSql(
            'ALTER TABLE organisations
             ADD public_reporting_identifier VARCHAR(20) DEFAULT NULL',
        );

        $usedIdentifiers = [];

        foreach ($organisationIds as $organisationId) {
            $identifier = $this->generateUniqueIdentifier(
                $usedIdentifiers,
            );
            $usedIdentifiers[$identifier] = true;

            $this->addSql(
                'UPDATE organisations
                 SET public_reporting_identifier = :identifier
                 WHERE id = :id',
                [
                    'identifier' => $identifier,
                    'id' => $organisationId,
                ],
            );
        }

        $this->addSql(
            'ALTER TABLE organisations
             ALTER public_reporting_identifier SET NOT NULL',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_D7E459AC97FD9F6B
             ON organisations (public_reporting_identifier)',
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
            'DROP INDEX UNIQ_D7E459AC97FD9F6B',
        );
        $this->addSql(
            'ALTER TABLE organisations
             DROP public_reporting_identifier',
        );
    }

    /**
     * @param array<string, true> $usedIdentifiers
     */
    private function generateUniqueIdentifier(
        array $usedIdentifiers,
    ): string
    {
        do {
            $payload = '';

            for (
                $position = 0;
                $position < self::PAYLOAD_LENGTH;
                ++$position
            ) {
                $payload .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $identifier = self::PREFIX . $payload;
        } while (isset($usedIdentifiers[$identifier]));

        return $identifier;
    }
}
