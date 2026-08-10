<?php

declare(strict_types=1);

namespace App\Tests\Demo\Application;

use App\Demo\Application\SeedFictionalDemo;
use App\Demo\Domain\FictionalDemoDataset;
use App\Demo\Presentation\Console\SeedFictionalDemoCommand;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedFictionalDemoTest extends PostgreSqlTestCase
{
    private const PASSWORD = 'A fictional demo password 70!';

    private Connection $connection;
    private SeedFictionalDemo $seeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->entityManager->getConnection();
        $this->seeder = self::getContainer()->get(SeedFictionalDemo::class);
    }

    public function testCommandRequiresTheSupportedExplicitDemoEnvironment(): void
    {
        self::assertSame(
            FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER,
            PublicReportingIdentifier::fromString(
                FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER,
            )->toString(),
        );

        $development = $this->command(environment: 'dev', demoMode: true);
        self::assertSame(Command::FAILURE, $development->execute([]));
        self::assertStringContainsString('supported only', $development->getDisplay());

        $disabled = $this->command(environment: 'test', demoMode: false);
        self::assertSame(Command::FAILURE, $disabled->execute([]));
        self::assertStringContainsString('demo mode is disabled', $disabled->getDisplay());

        $weakPassword = $this->command(
            environment: 'test',
            demoMode: true,
            password: 'too-short',
        );
        self::assertSame(Command::FAILURE, $weakPassword->execute([]));
        self::assertStringContainsString('at least 20 characters', $weakPassword->getDisplay());
    }

    public function testSeedingIsRepeatableAndResetRequiresExactConfirmation(): void
    {
        $seed = $this->command();
        self::assertSame(Command::SUCCESS, $seed->execute([]));
        self::assertSame(Command::SUCCESS, $seed->execute([]));
        self::assertStringNotContainsString(self::PASSWORD, $seed->getDisplay());

        $this->assertKnownDatasetCounts();
        $passwordHash = $this->connection->fetchOne(
            'SELECT password_hash FROM professionals WHERE id = :id',
            ['id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID],
        );
        self::assertIsString($passwordHash);
        self::assertTrue(password_verify(self::PASSWORD, $passwordHash));

        $this->connection->executeStatement(
            'INSERT INTO reports (
                id, organisation_id, situation_description, situation_context, status,
                public_reference, access_secret_hash, created_at, version
             ) VALUES (
                :id, :organisation_id, :description, :context, :status,
                :reference, :secret_hash, :created_at, 1
             )',
            [
                'id' => '019fe900-0000-7000-8000-000000000099',
                'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                'description' => 'A fictional visitor-created demo report.',
                'context' => 'unknown',
                'status' => 'received',
                'reference' => 'D0000000000000000099',
                'secret_hash' => hash('sha256', 'fictional-extra-report'),
                'created_at' => '2026-08-10T10:00:00+02:00',
            ],
        );
        self::assertSame(5, $this->demoReportCount());

        $refusedReset = $this->command();
        self::assertSame(Command::FAILURE, $refusedReset->execute([
            '--reset' => true,
            '--confirm-reset' => 'wrong',
        ]));
        self::assertSame(5, $this->demoReportCount());

        $confirmedReset = $this->command();
        self::assertSame(Command::SUCCESS, $confirmedReset->execute([
            '--reset' => true,
            '--confirm-reset' => FictionalDemoDataset::RESET_CONFIRMATION,
        ]));
        self::assertStringContainsString('restored', $confirmedReset->getDisplay());
        $this->assertKnownDatasetCounts();
    }

    public function testSeedingRefusesReservedIdentifierCollisions(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO organisations (id, name, public_reporting_identifier)
             VALUES (:id, :name, :identifier)',
            [
                'id' => '019fe900-0000-7000-8000-000000000098',
                'name' => 'Unrelated fictional organisation',
                'identifier' => FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER,
            ],
        );

        $command = $this->command();
        self::assertSame(Command::FAILURE, $command->execute([]));
        self::assertStringContainsString('reserved demo organisation', $command->getDisplay());
        self::assertSame(0, $this->demoReportCount());
    }

    public function testResetRefusesToDeleteAReservedProfessionalWithExternalMembership(): void
    {
        self::assertSame(Command::SUCCESS, $this->command()->execute([]));
        $this->connection->executeStatement(
            'INSERT INTO organisations (id, name, public_reporting_identifier)
             VALUES (:id, :name, :identifier)',
            [
                'id' => '019fe900-0000-7000-8000-000000000097',
                'name' => 'Another fictional organisation',
                'identifier' => 'ORG_ABC0000000000000',
            ],
        );
        $this->connection->executeStatement(
            'INSERT INTO organisation_memberships (
                id, professional_id, organisation_id, role, granted_at, revoked_at
             ) VALUES (
                :id, :professional_id, :organisation_id, :role, :granted_at, NULL
             )',
            [
                'id' => '019fe900-0000-7000-8000-000000000096',
                'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'organisation_id' => '019fe900-0000-7000-8000-000000000097',
                'role' => 'triage',
                'granted_at' => '2026-08-10T10:00:00+02:00',
            ],
        );

        $reset = $this->command();
        self::assertSame(Command::FAILURE, $reset->execute([
            '--reset' => true,
            '--confirm-reset' => FictionalDemoDataset::RESET_CONFIRMATION,
        ]));
        self::assertStringContainsString('belongs to another organisation', $reset->getDisplay());
        $this->assertKnownDatasetCounts();
    }

    private function command(
        string $environment = 'test',
        bool $demoMode = true,
        string $password = self::PASSWORD,
    ): CommandTester {
        return new CommandTester(new SeedFictionalDemoCommand(
            $this->seeder,
            $environment,
            $demoMode,
            $password,
        ));
    }

    private function assertKnownDatasetCounts(): void
    {
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM organisations WHERE id = :id',
            ['id' => FictionalDemoDataset::ORGANISATION_ID],
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM professionals WHERE id IN (:triage, :administrator)',
            [
                'triage' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
            ],
        ));
        self::assertSame(4, $this->demoReportCount());
        self::assertSame(4, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM report_follow_up_entries WHERE report_id IN (
                SELECT id FROM reports WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        ));
    }

    private function demoReportCount(): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM reports WHERE organisation_id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
    }
}
