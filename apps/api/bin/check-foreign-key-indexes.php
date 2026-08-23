<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\DBAL\Connection;

require dirname(__DIR__).'/vendor/autoload.php';

$environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$kernel = new Kernel($environment, false);
$kernel->boot();

try {
    $connection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
    if (!$connection instanceof Connection) {
        throw new RuntimeException('Doctrine default connection is unavailable.');
    }

    assertNoMissingForeignKeyIndexes($connection);

    if (in_array('--exercise', $_SERVER['argv'], true)) {
        exerciseRegressionDetection($connection, $environment);
    }

    fwrite(STDOUT, "Every public foreign key has a supporting referencing-side index.\n");
} finally {
    $kernel->shutdown();
}

function assertNoMissingForeignKeyIndexes(Connection $connection): void
{
    $missingIndexes = findMissingForeignKeyIndexes($connection);

    if ([] !== $missingIndexes) {
        failForMissingForeignKeyIndexes($missingIndexes);
    }
}

/** @return list<array{table_name: string, constraint_name: string, definition: string}> */
function findMissingForeignKeyIndexes(Connection $connection): array
{
    return $connection->fetchAllAssociative(<<<'SQL'
SELECT
    foreign_key_table.relname AS table_name,
    constraint_definition.conname AS constraint_name,
    pg_get_constraintdef(constraint_definition.oid) AS definition
FROM pg_constraint AS constraint_definition
INNER JOIN pg_class AS foreign_key_table
    ON foreign_key_table.oid = constraint_definition.conrelid
INNER JOIN pg_namespace AS table_schema
    ON table_schema.oid = foreign_key_table.relnamespace
WHERE constraint_definition.contype = 'f'
    AND table_schema.nspname = 'public'
    AND NOT EXISTS (
        SELECT 1
        FROM pg_index AS supporting_index
        WHERE supporting_index.indrelid = constraint_definition.conrelid
            AND supporting_index.indisvalid
            AND supporting_index.indisready
            AND supporting_index.indpred IS NULL
            AND (supporting_index.indkey::smallint[])[0:array_length(constraint_definition.conkey, 1) - 1]
                = constraint_definition.conkey
    )
ORDER BY foreign_key_table.relname, constraint_definition.conname
SQL);
}

/** @param list<array{table_name: string, constraint_name: string, definition: string}> $missingIndexes */
function failForMissingForeignKeyIndexes(array $missingIndexes): never
{
    fwrite(STDERR, "Foreign keys without a supporting referencing-side index:\n");

    foreach ($missingIndexes as $missingIndex) {
        fwrite(
            STDERR,
            sprintf(
                "- %s.%s: %s\n",
                $missingIndex['table_name'],
                $missingIndex['constraint_name'],
                $missingIndex['definition'],
            ),
        );
    }

    exit(1);
}

function exerciseRegressionDetection(Connection $connection, string $environment): void
{
    if ('test' !== $environment) {
        throw new RuntimeException('The foreign-key regression exercise is only allowed in APP_ENV=test.');
    }

    $connection->executeStatement('CREATE TABLE foreign_key_index_check_parent (id UUID PRIMARY KEY)');
    $connection->executeStatement(<<<'SQL'
CREATE TABLE foreign_key_index_check_child (
    id UUID PRIMARY KEY,
    parent_id UUID NOT NULL,
    CONSTRAINT fk_foreign_key_index_check_parent
        FOREIGN KEY (parent_id) REFERENCES foreign_key_index_check_parent (id)
)
SQL);

    try {
        $missingBeforeIndex = findMissingForeignKeyIndexes($connection);
        $exerciseIsDetected = array_filter(
            $missingBeforeIndex,
            static fn (array $missingIndex): bool => 'foreign_key_index_check_child' === $missingIndex['table_name']
                && 'fk_foreign_key_index_check_parent' === $missingIndex['constraint_name'],
        );

        if ([] === $exerciseIsDetected) {
            throw new RuntimeException('The foreign-key index guard did not detect the deliberately unindexed foreign key.');
        }

        $connection->executeStatement('CREATE INDEX idx_foreign_key_index_check_parent ON foreign_key_index_check_child (parent_id)');
        assertNoMissingForeignKeyIndexes($connection);
    } finally {
        $connection->executeStatement('DROP TABLE IF EXISTS foreign_key_index_check_child');
        $connection->executeStatement('DROP TABLE IF EXISTS foreign_key_index_check_parent');
    }

    fwrite(STDOUT, "Foreign-key regression exercise detected and corrected an unindexed reference.\n");
}
