<?php

declare(strict_types=1);

namespace App\Demo\Application;

use App\Demo\Domain\FictionalDemoDataset;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class SeedFictionalDemo
{
    public function __construct(
        private Connection $connection,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function seed(string $professionalPassword, bool $reset): FictionalDemoSeedResult
    {
        return $this->connection->transactional(function () use ($professionalPassword, $reset): FictionalDemoSeedResult {
            $this->assertReservedIdentifiersAreSafe();

            if ($reset) {
                $this->removeDemoOrganisationData();
            }

            $this->upsertOrganisation();
            $this->upsertProfessionals($professionalPassword);
            $this->upsertMemberships();
            $this->upsertReports();
            $this->upsertConversationEntries();

            return new FictionalDemoSeedResult(1, 2, 4, 4, $reset);
        });
    }

    private function assertReservedIdentifiersAreSafe(): void
    {
        $organisation = $this->connection->fetchAssociative(
            'SELECT id, public_reporting_identifier FROM organisations
             WHERE id = :id OR public_reporting_identifier = :identifier',
            [
                'id' => FictionalDemoDataset::ORGANISATION_ID,
                'identifier' => FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER,
            ],
        );

        if ($organisation !== false && (
            $organisation['id'] !== FictionalDemoDataset::ORGANISATION_ID
            || $organisation['public_reporting_identifier'] !== FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER
        )) {
            throw new FictionalDemoDatasetConflict('The reserved demo organisation identifiers are already in use.');
        }

        $professionals = $this->connection->fetchAllAssociative(
            'SELECT id, email FROM professionals
             WHERE id IN (:triage_id, :administrator_id)
                OR email IN (:triage_email, :administrator_email)',
            [
                'triage_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'triage_email' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
                'administrator_email' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL,
            ],
        );
        $expectedProfessionals = [
            FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
            FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL,
        ];

        foreach ($professionals as $professional) {
            if (($expectedProfessionals[$professional['id']] ?? null) !== $professional['email']) {
                throw new FictionalDemoDatasetConflict('The reserved demo professional identifiers are already in use.');
            }
        }

        $externalMemberships = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM organisation_memberships
             WHERE professional_id IN (:triage_id, :administrator_id)
               AND organisation_id <> :organisation_id',
            [
                'triage_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
            ],
        );

        if ((int) $externalMemberships !== 0) {
            throw new FictionalDemoDatasetConflict('A reserved demo professional belongs to another organisation.');
        }

        $reports = $this->connection->fetchAllAssociative(
            'SELECT id, public_reference, organisation_id FROM reports
             WHERE id IN (:id_1, :id_2, :id_3, :id_4)
                OR public_reference IN (:reference_1, :reference_2, :reference_3, :reference_4)',
            $this->reportIdentifierParameters(),
        );
        $expectedReports = array_combine(
            FictionalDemoDataset::REPORT_IDS,
            FictionalDemoDataset::REPORT_REFERENCES,
        );

        foreach ($reports as $report) {
            if (
                ($expectedReports[$report['id']] ?? null) !== $report['public_reference']
                || $report['organisation_id'] !== FictionalDemoDataset::ORGANISATION_ID
            ) {
                throw new FictionalDemoDatasetConflict('The reserved demo report identifiers are already in use.');
            }
        }
    }

    private function removeDemoOrganisationData(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM report_access_grants WHERE report_id IN (
                SELECT id FROM reports WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM report_follow_up_entries WHERE report_id IN (
                SELECT id FROM reports WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM reports WHERE organisation_id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM organisation_memberships WHERE organisation_id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM professionals WHERE id IN (:triage_id, :administrator_id)',
            [
                'triage_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
            ],
        );
        $this->connection->executeStatement(
            'DELETE FROM organisations WHERE id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
    }

    private function upsertOrganisation(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO organisations (id, name, public_reporting_identifier)
             VALUES (:id, :name, :identifier)
             ON CONFLICT (id) DO UPDATE SET
                name = EXCLUDED.name,
                public_reporting_identifier = EXCLUDED.public_reporting_identifier',
            [
                'id' => FictionalDemoDataset::ORGANISATION_ID,
                'name' => FictionalDemoDataset::ORGANISATION_NAME,
                'identifier' => FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER,
            ],
        );
    }

    private function upsertProfessionals(string $professionalPassword): void
    {
        $createdAt = new DateTimeImmutable('2026-08-10T08:00:00+02:00');
        $professionals = [
            [
                'id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'name' => 'Lucía Demo',
                'email' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
            ],
            [
                'id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'name' => 'Carlos Demo',
                'email' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL,
            ],
        ];

        foreach ($professionals as $data) {
            $professional = new Professional(
                Uuid::fromString($data['id']),
                $data['name'],
                ProfessionalEmail::fromString($data['email']),
                $createdAt,
            );
            $passwordHash = $this->passwordHasher->hashPassword($professional, $professionalPassword);

            $this->connection->executeStatement(
                'INSERT INTO professionals (
                    id, name, email, created_at, password_hash, active, security_revision
                 ) VALUES (
                    :id, :name, :email, :created_at, :password_hash, TRUE, 1
                 )
                 ON CONFLICT (id) DO UPDATE SET
                    name = EXCLUDED.name,
                    email = EXCLUDED.email,
                    active = TRUE',
                [
                    ...$data,
                    'created_at' => $createdAt->format(DATE_ATOM),
                    'password_hash' => $passwordHash,
                ],
            );
        }
    }

    private function upsertMemberships(): void
    {
        $memberships = [
            [
                'id' => '019fe900-0000-7000-8000-000000000077',
                'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'role' => 'triage',
            ],
            [
                'id' => '019fe900-0000-7000-8000-000000000078',
                'professional_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'role' => 'administrator',
            ],
        ];

        foreach ($memberships as $membership) {
            $this->connection->executeStatement(
                'INSERT INTO organisation_memberships (
                    id, professional_id, organisation_id, role, granted_at, revoked_at
                 ) VALUES (
                    :id, :professional_id, :organisation_id, :role, :granted_at, NULL
                 )
                 ON CONFLICT (professional_id, organisation_id, role) DO UPDATE SET
                    revoked_at = NULL',
                [
                    ...$membership,
                    'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                    'granted_at' => '2026-08-10T08:00:00+02:00',
                ],
            );
        }
    }

    private function upsertReports(): void
    {
        $reports = [
            ['context' => 'in_person', 'status' => 'received', 'created_at' => '2026-08-10T08:35:00+02:00', 'description' => 'Una alumna ficticia ha dejado de participar en el recreo y parece preocupada desde hace varios días.'],
            ['context' => 'digital', 'status' => 'received', 'created_at' => '2026-08-09T17:20:00+02:00', 'description' => 'En un grupo ficticio de mensajería se han compartido comentarios excluyentes sobre un compañero ficticio.'],
            ['context' => 'mixed', 'status' => 'reviewed', 'created_at' => '2026-08-08T12:10:00+02:00', 'description' => 'Una discusión ficticia iniciada en clase ha continuado después a través de mensajes digitales.'],
            ['context' => 'unknown', 'status' => 'reviewed', 'created_at' => '2026-08-07T09:40:00+02:00', 'description' => 'Una persona informante ficticia solicita orientación sobre un cambio de comportamiento observado.'],
        ];

        foreach ($reports as $index => $report) {
            $reviewed = $report['status'] === 'reviewed';
            $this->connection->executeStatement(
                'INSERT INTO reports (
                    id, organisation_id, situation_description, situation_context, status,
                    public_reference, access_secret_hash, created_at, review_reason,
                    reviewed_by_professional_id, reviewed_at, version
                 ) VALUES (
                    :id, :organisation_id, :description, :context, :status,
                    :public_reference, :access_secret_hash, :created_at, :review_reason,
                    :reviewed_by, :reviewed_at, 1
                 )
                 ON CONFLICT (id) DO UPDATE SET
                    organisation_id = EXCLUDED.organisation_id,
                    situation_description = EXCLUDED.situation_description,
                    situation_context = EXCLUDED.situation_context,
                    status = EXCLUDED.status,
                    public_reference = EXCLUDED.public_reference,
                    access_secret_hash = EXCLUDED.access_secret_hash,
                    created_at = EXCLUDED.created_at,
                    review_reason = EXCLUDED.review_reason,
                    reviewed_by_professional_id = EXCLUDED.reviewed_by_professional_id,
                    reviewed_at = EXCLUDED.reviewed_at',
                [
                    'id' => FictionalDemoDataset::REPORT_IDS[$index],
                    'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                    'description' => $report['description'],
                    'context' => $report['context'],
                    'status' => $report['status'],
                    'public_reference' => FictionalDemoDataset::REPORT_REFERENCES[$index],
                    'access_secret_hash' => hash('sha256', 'convive-fictional-demo-report-'.($index + 1)),
                    'created_at' => $report['created_at'],
                    'review_reason' => $reviewed ? 'Revisión inicial ficticia completada para la demostración.' : null,
                    'reviewed_by' => $reviewed ? FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID : null,
                    'reviewed_at' => $reviewed ? '2026-08-10T09:00:00+02:00' : null,
                ],
            );
        }
    }

    private function upsertConversationEntries(): void
    {
        $entries = [
            ['id' => '019fe900-0000-7000-8000-000000000079', 'report' => 0, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-10T08:50:00+02:00', 'content' => 'La situación ficticia volvió a observarse durante el recreo de hoy.'],
            ['id' => '019fe900-0000-7000-8000-000000000080', 'report' => 0, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-10T09:10:00+02:00', 'content' => 'Gracias por la información ficticia. El centro ha iniciado una primera revisión.'],
            ['id' => '019fe900-0000-7000-8000-000000000081', 'report' => 2, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-09T10:00:00+02:00', 'content' => 'Añado un detalle ficticio para contextualizar cuándo continuaron los mensajes.'],
            ['id' => '019fe900-0000-7000-8000-000000000082', 'report' => 2, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-10T09:15:00+02:00', 'content' => 'La revisión ficticia se ha registrado y seguiremos informando por este canal.'],
        ];

        foreach ($entries as $entry) {
            $this->connection->executeStatement(
                'INSERT INTO report_follow_up_entries (
                    id, report_id, author_type, professional_author_id, content, created_at
                 ) VALUES (
                    :id, :report_id, :author_type, :professional_author_id, :content, :created_at
                 )
                 ON CONFLICT (id) DO UPDATE SET
                    report_id = EXCLUDED.report_id,
                    author_type = EXCLUDED.author_type,
                    professional_author_id = EXCLUDED.professional_author_id,
                    content = EXCLUDED.content,
                    created_at = EXCLUDED.created_at',
                [
                    'id' => $entry['id'],
                    'report_id' => FictionalDemoDataset::REPORT_IDS[$entry['report']],
                    'author_type' => $entry['author'],
                    'professional_author_id' => $entry['professional'],
                    'content' => $entry['content'],
                    'created_at' => $entry['created_at'],
                ],
            );
        }
    }

    /** @return array<string, string> */
    private function reportIdentifierParameters(): array
    {
        $parameters = [];

        foreach (FictionalDemoDataset::REPORT_IDS as $index => $id) {
            $parameters['id_'.($index + 1)] = $id;
            $parameters['reference_'.($index + 1)] = FictionalDemoDataset::REPORT_REFERENCES[$index];
        }

        return $parameters;
    }
}
