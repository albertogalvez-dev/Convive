<?php

declare(strict_types=1);

namespace App\Demo\Application;

use App\Demo\Domain\FictionalDemoDataset;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Domain\AttachmentDescription;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class SeedFictionalDemo
{
    public function __construct(
        private Connection $connection,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private AttachmentStorage $attachmentStorage,
        private ReportAttachmentRepository $attachments,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDirectory,
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
            $this->upsertManagedCase();
            $this->upsertAdditionalManagedCases();
            $this->upsertFictionalEvidence();

            return new FictionalDemoSeedResult(1, 5, 10, 14, 8, 10, 9, 5, $reset);
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
             WHERE id IN (:triage_id, :administrator_id, :case_lead_id, :case_contributor_id, :case_observer_id)
                OR email IN (:triage_email, :administrator_email, :case_lead_email, :case_contributor_email, :case_observer_email)',
            [
            'triage_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
            'administrator_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
            'case_lead_id' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID,
            'case_contributor_id' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID,
            'case_observer_id' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID,
            'triage_email' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
            'administrator_email' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL,
            'case_lead_email' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_EMAIL,
            'case_contributor_email' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_EMAIL,
            'case_observer_email' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_EMAIL,
            ],
        );
        $expectedProfessionals = [
            FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID => FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL,
            FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL,
            FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_EMAIL,
            FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_EMAIL,
            FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_EMAIL,
        ];

        foreach ($professionals as $professional) {
            if (($expectedProfessionals[$professional['id']] ?? null) !== $professional['email']) {
                throw new FictionalDemoDatasetConflict('The reserved demo professional identifiers are already in use.');
            }
        }

        $externalMemberships = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM organisation_memberships
             WHERE professional_id IN (:triage_id, :administrator_id, :case_lead_id, :case_contributor_id, :case_observer_id)
               AND organisation_id <> :organisation_id',
            [
                'triage_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'case_lead_id' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID,
                'case_contributor_id' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID,
                'case_observer_id' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID,
                'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
            ],
        );

        if ((int) $externalMemberships !== 0) {
            throw new FictionalDemoDatasetConflict('A reserved demo professional belongs to another organisation.');
        }

        $reportParameters = $this->reportIdentifierParameters();
        $reportIdPlaceholders = [];
        $reportReferencePlaceholders = [];
        foreach (array_keys(FictionalDemoDataset::REPORT_IDS) as $index) {
            $reportIdPlaceholders[] = ':id_'.($index + 1);
            $reportReferencePlaceholders[] = ':reference_'.($index + 1);
        }
        $reports = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT id, public_reference, organisation_id FROM reports WHERE id IN (%s) OR public_reference IN (%s)',
                implode(', ', $reportIdPlaceholders),
                implode(', ', $reportReferencePlaceholders),
            ),
            $reportParameters,
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

        $caseParameters = [];
        $casePlaceholders = [];
        foreach (FictionalDemoDataset::MANAGED_CASE_IDS as $index => $id) {
            $parameter = 'case_'.($index + 1);
            $caseParameters[$parameter] = $id;
            $casePlaceholders[] = ':'.$parameter;
        }
        $managedCases = $this->connection->fetchAllAssociative(
            sprintf('SELECT id, organisation_id FROM managed_cases WHERE id IN (%s)', implode(', ', $casePlaceholders)),
            $caseParameters,
        );

        foreach ($managedCases as $managedCase) {
            if ($managedCase['organisation_id'] !== FictionalDemoDataset::ORGANISATION_ID) {
                throw new FictionalDemoDatasetConflict('A reserved demo case identifier is already in use.');
            }
        }

        $attachmentParameters = [];
        $attachmentPlaceholders = [];
        foreach (array_keys(FictionalDemoDataset::EVIDENCE_REPORT_IDS) as $index => $id) {
            $parameter = 'attachment_'.($index + 1);
            $attachmentParameters[$parameter] = $id;
            $attachmentPlaceholders[] = ':'.$parameter;
        }
        $attachments = $this->connection->fetchAllAssociative(
            sprintf('SELECT id, report_id FROM report_attachments WHERE id IN (%s)', implode(', ', $attachmentPlaceholders)),
            $attachmentParameters,
        );

        foreach ($attachments as $attachment) {
            if ((FictionalDemoDataset::EVIDENCE_REPORT_IDS[$attachment['id']] ?? null) !== $attachment['report_id']) {
                throw new FictionalDemoDatasetConflict('A reserved demo attachment identifier is already in use.');
            }
        }
    }

    private function removeDemoOrganisationData(): void
    {
        /** @var list<ReportAttachment> $attachments */
        $attachments = $this->entityManager
            ->createQueryBuilder()
            ->select('attachment')
            ->from(ReportAttachment::class, 'attachment')
            ->join('attachment.report', 'report')
            ->join('report.organisation', 'organisation')
            ->where('organisation.id = :organisation_id')
            ->setParameter('organisation_id', FictionalDemoDataset::ORGANISATION_ID)
            ->getQuery()
            ->getResult();

        foreach ($attachments as $attachment) {
            $this->attachmentStorage->delete($attachment);
        }

        $this->connection->executeStatement(
            'DELETE FROM report_attachments WHERE report_id IN (
                SELECT id FROM reports WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
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
            'DELETE FROM report_triage_decisions WHERE organisation_id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM case_communications WHERE case_id IN (
                SELECT id FROM managed_cases WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM case_tasks WHERE case_id IN (
                SELECT id FROM managed_cases WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM case_involved_people WHERE case_id IN (
                SELECT id FROM managed_cases WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM case_assignments WHERE case_id IN (
                SELECT id FROM managed_cases WHERE organisation_id = :organisation_id
            )',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->connection->executeStatement(
            'DELETE FROM managed_cases WHERE organisation_id = :organisation_id',
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
            'DELETE FROM professionals WHERE id IN (:triage_id, :administrator_id, :case_lead_id, :case_contributor_id, :case_observer_id)',
            [
                'triage_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'administrator_id' => FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_ID,
                'case_lead_id' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID,
                'case_contributor_id' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID,
                'case_observer_id' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID,
            ],
        );
        $this->connection->executeStatement(
            'DELETE FROM organisations WHERE id = :organisation_id',
            ['organisation_id' => FictionalDemoDataset::ORGANISATION_ID],
        );
        $this->entityManager->clear();
    }

    private function upsertOrganisation(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO organisations (id, name, public_reporting_identifier, territorial_scope)
             VALUES (:id, :name, :identifier, :territorialScope)
             ON CONFLICT (id) DO UPDATE SET
                name = EXCLUDED.name,
                public_reporting_identifier = EXCLUDED.public_reporting_identifier,
                territorial_scope = EXCLUDED.territorial_scope',
            [
                'id' => FictionalDemoDataset::ORGANISATION_ID,
                'name' => FictionalDemoDataset::ORGANISATION_NAME,
                'identifier' => FictionalDemoDataset::PUBLIC_REPORTING_IDENTIFIER,
                // Explicit, not inferred: this fictional organisation has
                // always only ever seen the Andalucía profile (#249); this
                // just makes that assignment a real, visible fact instead of
                // an implicit global default.
                'territorialScope' => FictionalDemoDataset::TERRITORIAL_SCOPE,
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
        [
            'id' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID,
            'name' => 'Ana Responsable',
            'email' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_EMAIL,
        ],
        [
            'id' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID,
            'name' => 'Marta Colaboradora',
            'email' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_EMAIL,
        ],
        [
            'id' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID,
            'name' => 'Óscar Observador',
            'email' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_EMAIL,
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
                    id, name, email, created_at, password_hash, active, account_status, security_revision
                 ) VALUES (
                    :id, :name, :email, :created_at, :password_hash, TRUE, \'active\', 1
                 )
                 ON CONFLICT (id) DO UPDATE SET
                    name = EXCLUDED.name,
                    email = EXCLUDED.email,
                    active = TRUE,
                    account_status = \'active\',
                    password_hash = EXCLUDED.password_hash,
                    security_revision = professionals.security_revision + 1',
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
            [
                'id' => '019fe900-0000-7000-8000-000000000094',
                'professional_id' => FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_ID,
                'role' => 'triage',
            ],
            [
                'id' => '019fe900-0000-7000-8000-000000000095',
                'professional_id' => FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID,
                'role' => 'triage',
            ],
            [
                'id' => '019fe900-0000-7000-8000-000000000096',
                'professional_id' => FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID,
                'role' => 'triage',
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
            ['context' => 'in_person', 'status' => 'reviewed', 'created_at' => '2026-08-06T10:15:00+02:00', 'description' => 'Un cambio ficticio en la dinámica de un grupo durante varias semanas requiere una primera valoración cuidadosa.'],
            ['context' => 'digital', 'status' => 'received', 'created_at' => '2026-08-05T18:05:00+02:00', 'description' => 'Una persona informante ficticia comparte que han circulado mensajes que podrían estar aislando a un compañero.'],
            ['context' => 'mixed', 'status' => 'reviewed', 'created_at' => '2026-08-04T11:20:00+02:00', 'description' => 'Una situación ficticia observada en el patio se ha comentado también en un canal digital del alumnado.'],
            ['context' => 'in_person', 'status' => 'reviewed', 'created_at' => '2026-08-03T09:05:00+02:00', 'description' => 'Un familiar ficticio pide apoyo para entender un cambio sostenido en la asistencia y el ánimo.'],
            ['context' => 'unknown', 'status' => 'reviewed', 'created_at' => '2026-08-02T12:45:00+02:00', 'description' => 'Una comunicación ficticia sin detalles suficientes queda preparada para una revisión inicial del centro.'],
            ['context' => 'digital', 'status' => 'received', 'created_at' => '2026-08-01T16:40:00+02:00', 'description' => 'Se recibe una consulta ficticia sobre la convivencia en un espacio digital vinculado al grupo.'],
        ];

        foreach ($reports as $index => $report) {
            $reviewed = $report['status'] === 'reviewed';
            $this->connection->executeStatement(
                'INSERT INTO reports (
                    id, organisation_id, situation_description, situation_context, reporter_recurrence,
                    reporter_attention_cue, professional_concern_category,
                    professional_recurrence, professional_attention_cue, status,
                    public_reference, access_secret_hash, created_at, review_reason,
                    reviewed_by_professional_id, reviewed_at, version
                 ) VALUES (
                    :id, :organisation_id, :description, :context, :recurrence,
                    :attention_cue, :professional_concern_category,
                    :professional_recurrence, :professional_attention_cue, :status,
                    :public_reference, :access_secret_hash, :created_at, :review_reason,
                    :reviewed_by, :reviewed_at, 1
                 )
                 ON CONFLICT (id) DO UPDATE SET
                    organisation_id = EXCLUDED.organisation_id,
                    situation_description = EXCLUDED.situation_description,
                    situation_context = EXCLUDED.situation_context,
                    reporter_recurrence = EXCLUDED.reporter_recurrence,
                    reporter_attention_cue = EXCLUDED.reporter_attention_cue,
                    professional_concern_category = EXCLUDED.professional_concern_category,
                    professional_recurrence = EXCLUDED.professional_recurrence,
                    professional_attention_cue = EXCLUDED.professional_attention_cue,
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
                    'recurrence' => $index === 0 || $index === 2 ? 'ongoing' : ($index === 1 ? 'repeated' : 'unknown'),
                    'attention_cue' => $index === 0 || $index === 2 ? 'needs_prompt_attention' : ($index === 1 ? 'no_prompt_attention_indicated' : 'unknown'),
                    'professional_concern_category' => $reviewed ? ($index === 2 ? 'digital_interaction' : 'safety_or_wellbeing_concern') : null,
                    'professional_recurrence' => $reviewed ? ($index === 2 ? 'ongoing' : 'unknown') : null,
                    'professional_attention_cue' => $reviewed ? ($index === 2 ? 'needs_prompt_attention' : 'unknown') : null,
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
            ['id' => '019fe900-0000-7000-8000-000000000180', 'report' => 1, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-09T18:05:00+02:00', 'content' => 'La información ficticia se refiere a varios mensajes observados durante la última semana.'],
            ['id' => '019fe900-0000-7000-8000-000000000181', 'report' => 1, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-10T09:20:00+02:00', 'content' => 'Gracias. El centro revisará este contexto ficticio con el cuidado correspondiente.'],
            ['id' => '019fe900-0000-7000-8000-000000000182', 'report' => 4, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-06T10:35:00+02:00', 'content' => 'La situación ficticia se aprecia sobre todo en los momentos de cambio entre clases.'],
            ['id' => '019fe900-0000-7000-8000-000000000183', 'report' => 4, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-06T12:10:00+02:00', 'content' => 'Se ha abierto un seguimiento ficticio y se anotará cualquier avance relevante.'],
            ['id' => '019fe900-0000-7000-8000-000000000184', 'report' => 5, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-05T18:20:00+02:00', 'content' => 'No deseo identificar a nadie; comparto solo el contexto ficticio que he percibido.'],
            ['id' => '019fe900-0000-7000-8000-000000000185', 'report' => 6, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-04T13:00:00+02:00', 'content' => 'El caso ficticio ya dispone de un equipo asignado y de próximos pasos definidos.'],
            ['id' => '019fe900-0000-7000-8000-000000000186', 'report' => 7, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-03T09:30:00+02:00', 'content' => 'Agradezco que el centro pueda valorar esta situación ficticia de manera proporcionada.'],
            ['id' => '019fe900-0000-7000-8000-000000000187', 'report' => 7, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-03T11:00:00+02:00', 'content' => 'Se ha documentado la orientación ficticia y el seguimiento queda registrado.'],
            ['id' => '019fe900-0000-7000-8000-000000000188', 'report' => 8, 'author' => 'reporter', 'professional' => null, 'created_at' => '2026-08-02T13:05:00+02:00', 'content' => 'Por ahora no dispongo de más detalles ficticios, pero puedo ampliar la información si hace falta.'],
            ['id' => '019fe900-0000-7000-8000-000000000189', 'report' => 9, 'author' => 'professional', 'professional' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => '2026-08-01T17:10:00+02:00', 'content' => 'La comunicación ficticia se conserva para que el centro pueda revisarla cuando corresponda.'],
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

    private function upsertManagedCase(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO managed_cases (
                id, organisation_id, created_by_professional_id, created_at, operational_updated_at, status, modality
             ) VALUES (
                :id, :organisation_id, :professional_id, :created_at, :operational_updated_at, :status, :modality
             )
             ON CONFLICT (id) DO UPDATE SET
                organisation_id = EXCLUDED.organisation_id,
                created_by_professional_id = EXCLUDED.created_by_professional_id,
                created_at = EXCLUDED.created_at,
                operational_updated_at = EXCLUDED.operational_updated_at,
                status = EXCLUDED.status,
                modality = EXCLUDED.modality',
            [
                'id' => FictionalDemoDataset::MANAGED_CASE_ID,
                'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'created_at' => '2026-08-10T09:30:00+02:00',
                'operational_updated_at' => '2026-08-10T09:40:00+02:00',
                'status' => 'assessment',
                'modality' => 'mixed',
            ],
        );

        foreach ([
            [FictionalDemoDataset::CASE_CONTRIBUTOR_ASSIGNMENT_ID, FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_ID, 'contributor'],
            [FictionalDemoDataset::CASE_OBSERVER_ASSIGNMENT_ID, FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_ID, 'observer'],
        ] as [$id, $professionalId, $role]) {
            $this->connection->executeStatement(
                'INSERT INTO case_assignments (
                    id, case_id, professional_id, role, assigned_by_professional_id, assigned_at, revoked_at
                 ) VALUES (
                    :id, :case_id, :professional_id, :role, :assigned_by_professional_id, :assigned_at, NULL
                 )
                 ON CONFLICT (case_id, professional_id) DO UPDATE SET
                    id = EXCLUDED.id,
                    role = EXCLUDED.role,
                    assigned_by_professional_id = EXCLUDED.assigned_by_professional_id,
                    assigned_at = EXCLUDED.assigned_at,
                    revoked_at = NULL',
                [
                    'id' => $id,
                    'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
                    'professional_id' => $professionalId,
                    'role' => $role,
                    'assigned_by_professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                    'assigned_at' => '2026-08-10T09:35:00+02:00',
                ],
            );
        }

        $this->connection->executeStatement(
            'INSERT INTO report_triage_decisions (
                id, report_id, organisation_id, decided_by_professional_id, outcome,
                reason, decided_at, terminal_report_id, case_id
             ) VALUES (
                :id, :report_id, :organisation_id, :professional_id, :outcome,
                :reason, :decided_at, :report_id, :case_id
             )
             ON CONFLICT (id) DO UPDATE SET
                report_id = EXCLUDED.report_id,
                organisation_id = EXCLUDED.organisation_id,
                decided_by_professional_id = EXCLUDED.decided_by_professional_id,
                outcome = EXCLUDED.outcome,
                reason = EXCLUDED.reason,
                decided_at = EXCLUDED.decided_at,
                terminal_report_id = EXCLUDED.terminal_report_id,
                case_id = EXCLUDED.case_id',
            [
                'id' => FictionalDemoDataset::CASE_TRIAGE_DECISION_ID,
                'report_id' => FictionalDemoDataset::REPORT_IDS[2],
                'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'outcome' => 'link_to_case',
                'reason' => 'La valoración ficticia requiere seguimiento estructurado por el centro.',
                'decided_at' => '2026-08-10T09:30:00+02:00',
                'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
            ],
        );

        $this->connection->executeStatement(
            'INSERT INTO case_assignments (
                id, case_id, professional_id, role, assigned_by_professional_id, assigned_at, revoked_at
             ) VALUES (
                :id, :case_id, :professional_id, :role, :professional_id, :assigned_at, NULL
             )
             ON CONFLICT (case_id, professional_id) DO UPDATE SET
                id = EXCLUDED.id,
                role = EXCLUDED.role,
                assigned_by_professional_id = EXCLUDED.assigned_by_professional_id,
                assigned_at = EXCLUDED.assigned_at,
                revoked_at = NULL',
            [
                'id' => FictionalDemoDataset::CASE_ASSIGNMENT_ID,
                'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
                'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'role' => 'lead',
                'assigned_at' => '2026-08-10T09:30:00+02:00',
            ],
        );

        $people = [
            [
                'id' => FictionalDemoDataset::CASE_AFFECTED_PERSON_ID,
                'name' => 'Persona ficticia A',
                'role' => 'affected',
            ],
            [
                'id' => FictionalDemoDataset::CASE_WITNESS_PERSON_ID,
                'name' => 'Persona ficticia B',
                'role' => 'witness',
            ],
        ];

        foreach ($people as $person) {
            $this->connection->executeStatement(
                'INSERT INTO case_involved_people (
                    id, case_id, name, role, added_by_professional_id, added_at
                 ) VALUES (
                    :id, :case_id, :name, :role, :professional_id, :added_at
                 )
                 ON CONFLICT (id) DO UPDATE SET
                    case_id = EXCLUDED.case_id,
                    name = EXCLUDED.name,
                    role = EXCLUDED.role,
                    added_by_professional_id = EXCLUDED.added_by_professional_id,
                    added_at = EXCLUDED.added_at',
                [
                    ...$person,
                    'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
                    'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                    'added_at' => '2026-08-10T09:35:00+02:00',
                ],
            );
        }

        $this->connection->executeStatement(
            'INSERT INTO case_tasks (
                id, case_id, owner_professional_id, source_version_id, stage, kind, title,
                due_at, status, created_by_professional_id, created_at,
                resolved_by_professional_id, resolved_at, not_applicable_reason
             ) VALUES (
                :id, :case_id, :professional_id, :source_id, :stage, :kind, :title,
                :due_at, :status, :professional_id, :created_at, NULL, NULL, NULL
             )
             ON CONFLICT (id) DO UPDATE SET
                case_id = EXCLUDED.case_id,
                owner_professional_id = EXCLUDED.owner_professional_id,
                source_version_id = EXCLUDED.source_version_id,
                stage = EXCLUDED.stage,
                kind = EXCLUDED.kind,
                title = EXCLUDED.title,
                due_at = EXCLUDED.due_at,
                status = EXCLUDED.status,
                created_by_professional_id = EXCLUDED.created_by_professional_id,
                created_at = EXCLUDED.created_at,
                resolved_by_professional_id = NULL,
                resolved_at = NULL,
                not_applicable_reason = NULL',
            [
                'id' => FictionalDemoDataset::CASE_TASK_ID,
                'case_id' => FictionalDemoDataset::MANAGED_CASE_ID,
                'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                'source_id' => FictionalDemoDataset::ANDALUSIAN_PROTOCOL_SOURCE_ID,
                'stage' => 'inspection_communication',
                'kind' => 'external_communication',
                'title' => 'Confirmar la comunicación ficticia con Inspección Educativa',
                'due_at' => '2026-08-10T10:30:00+02:00',
                'status' => 'pending',
                'created_at' => '2026-08-10T09:40:00+02:00',
            ],
        );
    }

    private function upsertAdditionalManagedCases(): void
    {
        $cases = [
            [
                'id' => FictionalDemoDataset::ACTIVE_CASE_ID,
                'assignment_id' => FictionalDemoDataset::ACTIVE_CASE_ASSIGNMENT_ID,
                'decision_id' => FictionalDemoDataset::ACTIVE_CASE_TRIAGE_DECISION_ID,
                'person_id' => FictionalDemoDataset::ACTIVE_CASE_PERSON_ID,
                'task_id' => FictionalDemoDataset::ACTIVE_CASE_TASK_ID,
                'communication_id' => FictionalDemoDataset::ACTIVE_CASE_COMMUNICATION_ID,
                'report_id' => FictionalDemoDataset::REPORT_IDS[0],
                'created_at' => '2026-08-10T11:00:00+02:00',
                'updated_at' => '2026-08-11T10:30:00+02:00',
                'status' => 'active',
                'modality' => 'in_person',
                'person_name' => 'Persona ficticia C',
                'task_title' => 'Acordar el siguiente paso de acompañamiento ficticio',
                'task_status' => 'pending',
                'due_at' => '2026-08-26T10:00:00+02:00',
                'task_resolved_at' => null,
                'communication_note' => 'Se registra una conversación ficticia con la familia para acordar el siguiente contacto.',
            ],
            [
                'id' => FictionalDemoDataset::CLOSED_CASE_ID,
                'assignment_id' => FictionalDemoDataset::CLOSED_CASE_ASSIGNMENT_ID,
                'decision_id' => FictionalDemoDataset::CLOSED_CASE_TRIAGE_DECISION_ID,
                'person_id' => FictionalDemoDataset::CLOSED_CASE_PERSON_ID,
                'task_id' => FictionalDemoDataset::CLOSED_CASE_TASK_ID,
                'communication_id' => FictionalDemoDataset::CLOSED_CASE_COMMUNICATION_ID,
                'report_id' => FictionalDemoDataset::REPORT_IDS[3],
                'created_at' => '2026-08-07T10:00:00+02:00',
                'updated_at' => '2026-08-09T12:00:00+02:00',
                'status' => 'closed',
                'modality' => 'digital',
                'person_name' => 'Persona ficticia D',
                'task_title' => 'Registrar el cierre del acompañamiento ficticio',
                'task_status' => 'completed',
                'due_at' => '2026-08-09T10:00:00+02:00',
                'task_resolved_at' => '2026-08-09T11:00:00+02:00',
                'communication_note' => 'Se conserva un registro ficticio de la conversación final de seguimiento.',
            ],
            [
                'id' => FictionalDemoDataset::FIRST_RICH_CASE_ID,
                'assignment_id' => '019fe900-0000-7000-8000-000000000121',
                'decision_id' => '019fe900-0000-7000-8000-000000000122',
                'person_id' => '019fe900-0000-7000-8000-000000000123',
                'task_id' => '019fe900-0000-7000-8000-000000000124',
                'communication_id' => '019fe900-0000-7000-8000-000000000125',
                'report_id' => FictionalDemoDataset::REPORT_IDS[4],
                'created_at' => '2026-08-06T11:00:00+02:00',
                'updated_at' => '2026-08-12T09:15:00+02:00',
                'status' => 'assessment',
                'modality' => 'in_person',
                'person_name' => 'Persona ficticia E',
                'task_title' => 'Completar la valoración inicial ficticia con el equipo de bienestar',
                'task_status' => 'pending',
                'due_at' => '2026-08-27T09:30:00+02:00',
                'task_resolved_at' => null,
                'communication_note' => 'Se anota una coordinación ficticia para completar la primera valoración del caso.',
            ],
            [
                'id' => FictionalDemoDataset::SECOND_RICH_CASE_ID,
                'assignment_id' => '019fe900-0000-7000-8000-000000000131',
                'decision_id' => '019fe900-0000-7000-8000-000000000132',
                'person_id' => '019fe900-0000-7000-8000-000000000133',
                'task_id' => '019fe900-0000-7000-8000-000000000134',
                'communication_id' => '019fe900-0000-7000-8000-000000000135',
                'report_id' => FictionalDemoDataset::REPORT_IDS[5],
                'created_at' => '2026-08-05T18:30:00+02:00',
                'updated_at' => '2026-08-13T10:20:00+02:00',
                'status' => 'active',
                'modality' => 'digital',
                'person_name' => 'Persona ficticia F',
                'task_title' => 'Revisar la información adicional ficticia compartida por el equipo',
                'task_status' => 'pending',
                'due_at' => '2026-08-26T12:00:00+02:00',
                'task_resolved_at' => null,
                'communication_note' => 'Se registra una actualización ficticia del seguimiento compartida a través del canal seguro.',
            ],
            [
                'id' => FictionalDemoDataset::THIRD_RICH_CASE_ID,
                'assignment_id' => '019fe900-0000-7000-8000-000000000141',
                'decision_id' => '019fe900-0000-7000-8000-000000000142',
                'person_id' => '019fe900-0000-7000-8000-000000000143',
                'task_id' => '019fe900-0000-7000-8000-000000000144',
                'communication_id' => '019fe900-0000-7000-8000-000000000145',
                'report_id' => FictionalDemoDataset::REPORT_IDS[6],
                'created_at' => '2026-08-04T11:45:00+02:00',
                'updated_at' => '2026-08-14T13:00:00+02:00',
                'status' => 'active',
                'modality' => 'mixed',
                'person_name' => 'Persona ficticia G',
                'task_title' => 'Registrar la medida educativa ficticia ya acordada',
                'task_status' => 'completed',
                'due_at' => '2026-08-14T12:00:00+02:00',
                'task_resolved_at' => '2026-08-14T12:45:00+02:00',
                'communication_note' => 'Se conserva un registro ficticio de la coordinación entre profesionales y familia.',
            ],
            [
                'id' => FictionalDemoDataset::FOURTH_RICH_CASE_ID,
                'assignment_id' => '019fe900-0000-7000-8000-000000000151',
                'decision_id' => '019fe900-0000-7000-8000-000000000152',
                'person_id' => '019fe900-0000-7000-8000-000000000153',
                'task_id' => '019fe900-0000-7000-8000-000000000154',
                'communication_id' => '019fe900-0000-7000-8000-000000000155',
                'report_id' => FictionalDemoDataset::REPORT_IDS[7],
                'created_at' => '2026-08-03T09:30:00+02:00',
                'updated_at' => '2026-08-15T11:30:00+02:00',
                'status' => 'closed',
                'modality' => 'in_person',
                'person_name' => 'Persona ficticia H',
                'task_title' => 'Documentar el cierre del seguimiento ficticio con la familia',
                'task_status' => 'completed',
                'due_at' => '2026-08-15T10:30:00+02:00',
                'task_resolved_at' => '2026-08-15T11:15:00+02:00',
                'communication_note' => 'El cierre ficticio deja constancia de las medidas de acompañamiento acordadas.',
            ],
            [
                'id' => FictionalDemoDataset::FIFTH_RICH_CASE_ID,
                'assignment_id' => '019fe900-0000-7000-8000-000000000161',
                'decision_id' => '019fe900-0000-7000-8000-000000000162',
                'person_id' => '019fe900-0000-7000-8000-000000000163',
                'task_id' => '019fe900-0000-7000-8000-000000000164',
                'communication_id' => '019fe900-0000-7000-8000-000000000165',
                'report_id' => FictionalDemoDataset::REPORT_IDS[8],
                'created_at' => '2026-08-02T13:20:00+02:00',
                'updated_at' => '2026-08-16T09:40:00+02:00',
                'status' => 'assessment',
                'modality' => 'mixed',
                'person_name' => 'Persona ficticia I',
                'task_title' => 'Preparar la siguiente conversación ficticia de seguimiento',
                'task_status' => 'pending',
                'due_at' => '2026-08-28T10:00:00+02:00',
                'task_resolved_at' => null,
                'communication_note' => 'Se ha programado una conversación ficticia para seguir aclarando el contexto.',
            ],
        ];

        foreach ($cases as $case) {
            $this->connection->executeStatement(
                'INSERT INTO managed_cases (
                    id, organisation_id, created_by_professional_id, created_at, operational_updated_at,
                    status, modality, status_reason, status_evidence, status_changed_at
                 ) VALUES (
                    :id, :organisation_id, :professional_id, :created_at, :updated_at,
                    :status, :modality, :status_reason, :status_evidence, :updated_at
                 ) ON CONFLICT (id) DO UPDATE SET
                    organisation_id = EXCLUDED.organisation_id, created_by_professional_id = EXCLUDED.created_by_professional_id,
                    created_at = EXCLUDED.created_at, operational_updated_at = EXCLUDED.operational_updated_at,
                    status = EXCLUDED.status, modality = EXCLUDED.modality, status_reason = EXCLUDED.status_reason,
                    status_evidence = EXCLUDED.status_evidence, status_changed_at = EXCLUDED.status_changed_at',
                [
                    'id' => $case['id'],
                    'created_at' => $case['created_at'],
                    'updated_at' => $case['updated_at'],
                    'status' => $case['status'],
                    'modality' => $case['modality'],
                    'organisation_id' => FictionalDemoDataset::ORGANISATION_ID,
                    'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID,
                    'status_reason' => 'Estado ficticio preparado para mostrar un recorrido completo.',
                    'status_evidence' => 'Registro ficticio de demostración; no acredita una actuación real.',
                ],
            );
            $this->connection->executeStatement(
                'INSERT INTO case_assignments (id, case_id, professional_id, role, assigned_by_professional_id, assigned_at, revoked_at)
                 VALUES (:assignment_id, :id, :professional_id, :role, :professional_id, :created_at, NULL)
                 ON CONFLICT (case_id, professional_id) DO UPDATE SET id = EXCLUDED.id, role = EXCLUDED.role,
                 assigned_by_professional_id = EXCLUDED.assigned_by_professional_id, assigned_at = EXCLUDED.assigned_at, revoked_at = NULL',
                ['assignment_id' => $case['assignment_id'], 'id' => $case['id'], 'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'created_at' => $case['created_at'], 'role' => 'lead'],
            );
            $this->connection->executeStatement(
                'INSERT INTO report_triage_decisions (id, report_id, organisation_id, decided_by_professional_id, outcome, reason, decided_at, terminal_report_id, case_id)
                 VALUES (:decision_id, :report_id, :organisation_id, :professional_id, :outcome, :reason, :created_at, :report_id, :id)
                 ON CONFLICT (id) DO UPDATE SET report_id = EXCLUDED.report_id, organisation_id = EXCLUDED.organisation_id,
                 decided_by_professional_id = EXCLUDED.decided_by_professional_id, outcome = EXCLUDED.outcome, reason = EXCLUDED.reason,
                 decided_at = EXCLUDED.decided_at, terminal_report_id = EXCLUDED.terminal_report_id, case_id = EXCLUDED.case_id',
                ['decision_id' => $case['decision_id'], 'report_id' => $case['report_id'], 'id' => $case['id'], 'created_at' => $case['created_at'], 'organisation_id' => FictionalDemoDataset::ORGANISATION_ID, 'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'outcome' => 'link_to_case', 'reason' => 'La comunicación ficticia requiere un seguimiento estructurado y proporcionado.'],
            );
            $this->connection->executeStatement(
                'INSERT INTO case_involved_people (id, case_id, name, role, added_by_professional_id, added_at)
                 VALUES (:person_id, :id, :person_name, :role, :professional_id, :created_at)
                 ON CONFLICT (id) DO UPDATE SET case_id = EXCLUDED.case_id, name = EXCLUDED.name, role = EXCLUDED.role,
                 added_by_professional_id = EXCLUDED.added_by_professional_id, added_at = EXCLUDED.added_at',
                ['person_id' => $case['person_id'], 'id' => $case['id'], 'person_name' => $case['person_name'], 'created_at' => $case['created_at'], 'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'role' => 'affected'],
            );
            $this->connection->executeStatement(
                'INSERT INTO case_tasks (id, case_id, owner_professional_id, source_version_id, stage, kind, title, due_at, status, created_by_professional_id, created_at, resolved_by_professional_id, resolved_at, not_applicable_reason)
                 VALUES (:task_id, :id, :professional_id, :source_id, :stage, :kind, :task_title, :due_at, :task_status, :professional_id, :created_at, :resolved_by, :task_resolved_at, NULL)
                 ON CONFLICT (id) DO UPDATE SET case_id = EXCLUDED.case_id, owner_professional_id = EXCLUDED.owner_professional_id,
                 source_version_id = EXCLUDED.source_version_id, stage = EXCLUDED.stage, kind = EXCLUDED.kind, title = EXCLUDED.title,
                 due_at = EXCLUDED.due_at, status = EXCLUDED.status, created_by_professional_id = EXCLUDED.created_by_professional_id,
                 created_at = EXCLUDED.created_at, resolved_by_professional_id = EXCLUDED.resolved_by_professional_id,
                 resolved_at = EXCLUDED.resolved_at, not_applicable_reason = NULL',
                ['task_id' => $case['task_id'], 'id' => $case['id'], 'task_title' => $case['task_title'], 'due_at' => $case['due_at'], 'task_status' => $case['task_status'], 'created_at' => $case['created_at'], 'task_resolved_at' => $case['task_resolved_at'], 'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'source_id' => FictionalDemoDataset::ANDALUSIAN_PROTOCOL_SOURCE_ID, 'stage' => 'action_plan', 'kind' => 'internal_action', 'resolved_by' => $case['task_status'] === 'completed' ? FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID : null],
            );
            $this->connection->executeStatement(
                'INSERT INTO case_communications (id, case_id, responsible_professional_id, recipient, channel, status, occurred_at, note, created_by_professional_id, created_at, supersedes_communication_id)
                 VALUES (:communication_id, :id, :professional_id, :recipient, :channel, :status, :updated_at, :communication_note, :professional_id, :updated_at, NULL)
                 ON CONFLICT (id) DO UPDATE SET case_id = EXCLUDED.case_id, responsible_professional_id = EXCLUDED.responsible_professional_id,
                 recipient = EXCLUDED.recipient, channel = EXCLUDED.channel, status = EXCLUDED.status, occurred_at = EXCLUDED.occurred_at,
                 note = EXCLUDED.note, created_by_professional_id = EXCLUDED.created_by_professional_id, created_at = EXCLUDED.created_at, supersedes_communication_id = NULL',
                ['communication_id' => $case['communication_id'], 'id' => $case['id'], 'updated_at' => $case['updated_at'], 'communication_note' => $case['communication_note'], 'professional_id' => FictionalDemoDataset::TRIAGE_PROFESSIONAL_ID, 'recipient' => 'family', 'channel' => 'telephone', 'status' => 'recorded'],
            );
        }
    }

    private function upsertFictionalEvidence(): void
    {
        $evidence = [
            [
                'id' => FictionalDemoDataset::CORRIDOR_ATTACHMENT_ID,
                'report_id' => FictionalDemoDataset::REPORT_IDS[0],
                'file' => 'fictional-empty-corridor.png',
                'description' => 'Imagen ficticia de un pasillo vacío del centro para la demostración.',
                'created_at' => '2026-08-10T08:40:00+02:00',
            ],
            [
                'id' => FictionalDemoDataset::COURTYARD_ATTACHMENT_ID,
                'report_id' => FictionalDemoDataset::REPORT_IDS[2],
                'file' => 'fictional-empty-courtyard.png',
                'description' => 'Imagen ficticia de un patio vacío del centro para la demostración.',
                'created_at' => '2026-08-08T12:15:00+02:00',
            ],
            [
                'id' => '019fe900-0000-7000-8000-000000000170',
                'report_id' => FictionalDemoDataset::REPORT_IDS[4],
                'file' => 'fictional-empty-library.png',
                'description' => 'Imagen ficticia de una biblioteca vacía del centro para la demostración.',
                'created_at' => '2026-08-06T10:20:00+02:00',
            ],
            [
                'id' => '019fe900-0000-7000-8000-000000000171',
                'report_id' => FictionalDemoDataset::REPORT_IDS[6],
                'file' => 'fictional-empty-guidance-room.png',
                'description' => 'Imagen ficticia de un espacio de orientación vacío para la demostración.',
                'created_at' => '2026-08-04T11:35:00+02:00',
            ],
            [
                'id' => '019fe900-0000-7000-8000-000000000172',
                'report_id' => FictionalDemoDataset::REPORT_IDS[8],
                'file' => 'fictional-empty-corridor.png',
                'description' => 'Imagen ficticia de un pasillo vacío vinculada a un segundo contexto de demostración.',
                'created_at' => '2026-08-02T12:55:00+02:00',
            ],
        ];

        foreach ($evidence as $item) {
            if ((int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM report_attachments WHERE id = :id',
                ['id' => $item['id']],
            ) === 1) {
                continue;
            }

            $sourcePath = $this->projectDirectory.'/resources/fictional-demo-evidence/'.$item['file'];
            if (!is_file($sourcePath) || !is_readable($sourcePath)) {
                throw new \LogicException('A reviewed fictional demonstration evidence asset is unavailable.');
            }

            $report = $this->entityManager->find(Report::class, $item['report_id']);
            if (!$report instanceof Report) {
                throw new \LogicException('The fictional demonstration report is unavailable for its evidence.');
            }

            $attachmentId = Uuid::fromString($item['id']);
            $stored = $this->attachmentStorage->storeQuarantine($attachmentId, $sourcePath);
            $attachment = ReportAttachment::quarantine(
                $attachmentId,
                $report,
                AttachmentMediaType::Png,
                $stored->byteSize,
                $stored->contentHash,
                new DateTimeImmutable($item['created_at']),
                AttachmentDescription::fromNullable($item['description']),
            );

            try {
                $this->attachments->saveQuarantinedWithReportCapacity([$attachment]);
            } catch (\Throwable $exception) {
                try {
                    $this->attachmentStorage->delete($attachment);
                } catch (\Throwable) {
                    // Preserve the persistence failure; lifecycle cleanup reconciles a later orphan.
                }

                throw $exception;
            }
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
