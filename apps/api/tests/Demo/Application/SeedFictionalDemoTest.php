<?php

declare(strict_types=1);

namespace App\Tests\Demo\Application;

use App\Demo\Application\SeedFictionalDemo;
use App\Demo\Domain\FictionalDemoDataset;
use App\Demo\Presentation\Console\SeedFictionalDemoCommand;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Infrastructure\LocalPrivateAttachmentStorage;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class SeedFictionalDemoTest extends PostgreSqlTestCase
{
    private const PASSWORD = 'A fictional demo password 70!';
    private const RESEEDED_PASSWORD = 'A different fictional demo password 71!';

    private Connection $connection;
    private SeedFictionalDemo $seeder;
    private LocalPrivateAttachmentStorage $attachmentStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->entityManager->getConnection();
        $seeder = self::getContainer()->get(SeedFictionalDemo::class);
        self::assertInstanceOf(SeedFictionalDemo::class, $seeder);
        $this->seeder = $seeder;

        $attachmentStorage = self::getContainer()->get(AttachmentStorage::class);
        self::assertInstanceOf(LocalPrivateAttachmentStorage::class, $attachmentStorage);
        $this->attachmentStorage = $attachmentStorage;
        (new Filesystem())->remove($this->attachmentStorage->privateDirectory());
    }

    protected function tearDown(): void
    {
        if (isset($this->attachmentStorage)) {
            (new Filesystem())->remove($this->attachmentStorage->privateDirectory());
        }

        parent::tearDown();
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
        self::assertSame(11, $this->demoReportCount());
        $securityRevision = $this->connection->fetchOne(
            'SELECT security_revision FROM professionals WHERE id = :id',
            ['id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID],
        );
        self::assertIsNumeric($securityRevision);

        $reseed = $this->command(password: self::RESEEDED_PASSWORD);
        self::assertSame(Command::SUCCESS, $reseed->execute([]));
        self::assertStringNotContainsString(self::RESEEDED_PASSWORD, $reseed->getDisplay());
        $reseededHash = $this->connection->fetchOne(
            'SELECT password_hash FROM professionals WHERE id = :id',
            ['id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID],
        );
        self::assertIsString($reseededHash);
        self::assertFalse(password_verify(self::PASSWORD, $reseededHash));
        self::assertTrue(password_verify(self::RESEEDED_PASSWORD, $reseededHash));
        self::assertSame((int) $securityRevision + 1, (int) $this->connection->fetchOne(
            'SELECT security_revision FROM professionals WHERE id = :id',
            ['id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID],
        ));
        self::assertSame(11, $this->demoReportCount());

        $attachmentId = '019fe900-0000-7000-8000-000000000100';
        $this->connection->executeStatement(
            'INSERT INTO report_attachments (
                id, report_id, media_type, byte_size, content_hash, storage_key,
                description, status, created_at
             ) VALUES (
                :id, :report_id, :media_type, :byte_size, :content_hash, :storage_key,
                :description, :status, :created_at
             )',
            [
                'id' => $attachmentId,
                'report_id' => '019fe900-0000-7000-8000-000000000099',
                'media_type' => 'application/pdf',
                'byte_size' => 30,
                'content_hash' => hash('sha256', '%PDF-1.7 fictional evidence'),
                'storage_key' => 'quarantine/'.$attachmentId,
                'description' => 'Fictional reset evidence.',
                'status' => 'quarantined',
                'created_at' => '2026-08-10T10:01:00+02:00',
            ],
        );
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM report_attachments WHERE id = :id',
            ['id' => $attachmentId],
        ));

        $refusedReset = $this->command();
        self::assertSame(Command::FAILURE, $refusedReset->execute([
            '--reset' => true,
            '--confirm-reset' => 'wrong',
        ]));
        self::assertSame(11, $this->demoReportCount());

        $confirmedReset = $this->command();
        self::assertSame(Command::SUCCESS, $confirmedReset->execute([
            '--reset' => true,
            '--confirm-reset' => FictionalDemoDataset::RESET_CONFIRMATION,
        ]));
        self::assertStringContainsString('restored', $confirmedReset->getDisplay());
        $this->assertKnownDatasetCounts();
        self::assertSame(0, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM report_attachments WHERE id = :id',
            ['id' => $attachmentId],
        ));
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
                'id' => '019fe900-0000-7000-8000-000000000098',
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
        self::assertSame(5, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM professionals WHERE id IN (:triage, :administrator, :case_lead, :case_contributor, :case_observer)',
            [
                'triage' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'case_lead' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID,
                'case_contributor' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID,
                'case_observer' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID,
            ],
        ));
        self::assertSame(10, $this->demoReportCount());
        self::assertSame(5, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM report_attachments
             WHERE report_id IN (
                SELECT id FROM reports WHERE organisation_id = :organisation_id
             )
               AND status = :status',
            [
                'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                'status' => 'quarantined',
            ],
        ));
        self::assertSame(
            FictionalDemoDataset::REPORT_IDS[0],
            $this->connection->fetchOne(
                'SELECT report_id FROM report_attachments WHERE id = :id',
                ['id' => FictionalDemoDataset::CORRIDOR_ATTACHMENT_ID],
            ),
        );
        self::assertSame(
            FictionalDemoDataset::REPORT_IDS[2],
            $this->connection->fetchOne(
                'SELECT report_id FROM report_attachments WHERE id = :id',
                ['id' => FictionalDemoDataset::COURTYARD_ATTACHMENT_ID],
            ),
        );
        self::assertSame(14, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM report_follow_up_entries WHERE report_id IN (
                SELECT id FROM reports WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        ));
        self::assertSame(8, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM managed_cases WHERE organisation_id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM managed_cases WHERE id = :id AND status = :status AND modality = :modality',
            ['id' => FictionalDemoDataset::MANAGED_CASE_ID, 'status' => 'assessment', 'modality' => 'mixed'],
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM managed_cases WHERE id = :id AND status = :status AND modality = :modality',
            ['id' => FictionalDemoDataset::ACTIVE_CASE_ID, 'status' => 'active', 'modality' => 'in_person'],
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM managed_cases WHERE id = :id AND status = :status AND modality = :modality',
            ['id' => FictionalDemoDataset::CLOSED_CASE_ID, 'status' => 'closed', 'modality' => 'digital'],
        ));
        self::assertSame(
            '2026-08-10 07:40:00',
            $this->connection->fetchOne(
                "SELECT to_char(operational_updated_at AT TIME ZONE 'UTC', 'YYYY-MM-DD HH24:MI:SS')
                 FROM managed_cases WHERE id = :id",
                ['id' => FictionalDemoDataset::MANAGED_CASE_ID],
            ),
        );
        self::assertSame(3, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_assignments
             WHERE case_id = :case_id AND revoked_at IS NULL
               AND role IN (:lead, :contributor, :observer)',
            [
                'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
                'lead' => 'lead',
                'contributor' => 'contributor',
                'observer' => 'observer',
            ],
        ));
        self::assertSame(10, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_assignments WHERE revoked_at IS NULL',
        ));
        self::assertSame(8, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_assignments WHERE professional_id = :professional_id AND revoked_at IS NULL',
            ['professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID],
        ));
        self::assertSame(2, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_involved_people WHERE case_id = :id',
            ['id' => FictionalDemoDataset::MANAGED_CASE_ID],
        ));
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_tasks
             WHERE id = :id AND case_id = :case_id AND status = :status
               AND kind = :kind AND resolved_at IS NULL',
            [
                'id' => FictionalDemoDataset::CASE_TASK_ID,
                'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
                'status' => 'pending',
                'kind' => 'external_communication',
            ],
        ));
        self::assertSame(
            'Confirmar la comunicación ficticia con Inspección Educativa',
            $this->connection->fetchOne(
                'SELECT title FROM case_tasks WHERE id = :id',
                ['id' => FictionalDemoDataset::CASE_TASK_ID],
            ),
        );
        self::assertSame(1, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_tasks WHERE id = :id AND status = :status AND resolved_at IS NOT NULL',
            ['id' => FictionalDemoDataset::CLOSED_CASE_TASK_ID, 'status' => 'completed'],
        ));
        self::assertSame(7, (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM case_communications WHERE case_id IN (
                SELECT id FROM managed_cases WHERE organisation_id = :organisation_id
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
