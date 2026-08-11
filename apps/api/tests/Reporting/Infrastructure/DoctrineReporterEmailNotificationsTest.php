<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReporterEmailAddress;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Infrastructure\DoctrineReporterEmailNotifications;
use App\Reporting\Infrastructure\DoctrineReportFollowUpEntryRepository;
use App\Reporting\Infrastructure\DoctrineReportRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class DoctrineReporterEmailNotificationsTest extends PostgreSqlTestCase
{
    private DoctrineReporterEmailNotifications $notifications;
    private DoctrineReportFollowUpEntryRepository $entries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifications = new DoctrineReporterEmailNotifications(
            $this->entityManager->getConnection(),
        );
        $this->entries = new DoctrineReportFollowUpEntryRepository(
            $this->entityManager,
            $this->notifications,
        );
    }

    public function testOnlyAVerifiedContactReceivesReportUpdateJobs(): void
    {
        $report = $this->createPersistedReport();
        self::assertSame('none', $this->notifications->status($report));
        self::assertSame('pending', $this->notifications->configure(
            $report,
            ReporterEmailAddress::fromString(' Reporter@Example.test '),
            'reporter-email-v1',
        ));

        $this->addProfessionalEntry($report, 'Sensitive first reply.');
        self::assertSame(0, $this->countJobs('report_update'));

        $verification = $this->notifications->claim();
        self::assertNotNull($verification);
        self::assertSame('verification', $verification->kind);
        self::assertSame('reporter@example.test', $verification->email);
        $token = $this->notifications->prepareVerificationToken($verification->contactId);
        self::assertFalse($this->notifications->verify(str_repeat('0', 64)));
        self::assertTrue($this->notifications->verify($token));
        self::assertFalse($this->notifications->verify($token), 'Verification tokens are single use.');

        $this->addProfessionalEntry($report, 'Sensitive second reply.');
        self::assertSame(1, $this->countJobs('report_update'));
        self::assertSame('verified', $this->notifications->status($report));
    }

    public function testRemovingAContactCancelsAllPendingDelivery(): void
    {
        $report = $this->createPersistedReport();
        $this->notifications->configure(
            $report,
            ReporterEmailAddress::fromString('reporter@example.test'),
            'reporter-email-v1',
        );

        self::assertSame(1, $this->countJobs('verification'));
        $this->notifications->remove($report);

        self::assertSame('none', $this->notifications->status($report));
        self::assertSame(0, $this->countJobs('verification'));
        self::assertNull($this->notifications->claim());
    }

    public function testExpiredPendingContactsAndOldDeliveryEvidenceArePurged(): void
    {
        $report = $this->createPersistedReport();
        $this->notifications->configure(
            $report,
            ReporterEmailAddress::fromString('reporter@example.test'),
            'reporter-email-v1',
        );
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            "UPDATE reporter_email_contacts SET updated_at = NOW() - INTERVAL '25 hours'",
        );

        self::assertSame(
            ['contacts' => 1, 'deliveries' => 0],
            $this->notifications->purgeExpired(),
        );
        self::assertSame('none', $this->notifications->status($report));
    }

    public function testDeliveryRetriesAreBoundedAndPersistTheirOutcome(): void
    {
        $report = $this->createPersistedReport();
        $this->notifications->configure(
            $report,
            ReporterEmailAddress::fromString('reporter@example.test'),
            'reporter-email-v1',
        );
        $connection = $this->entityManager->getConnection();

        for ($attempt = 1; $attempt <= 3; ++$attempt) {
            $delivery = $this->notifications->claim();
            self::assertNotNull($delivery);
            self::assertSame($attempt, $delivery->attempt);
            $this->notifications->markFailed($delivery);
            $connection->executeStatement(
                "UPDATE reporter_notification_outbox SET available_at = NOW() - INTERVAL '1 second'",
            );
        }

        self::assertNull($this->notifications->claim());
        self::assertSame(
            ['status' => 'failed', 'attempts' => 3],
            $connection->fetchAssociative(
                'SELECT status, attempts FROM reporter_notification_outbox',
            ),
        );
    }

    private function addProfessionalEntry(Report $report, string $content): void
    {
        self::assertTrue($this->entries->saveIfReportHasCapacity(
            ReportFollowUpEntry::addedByProfessional(
                $report,
                Uuid::v7(),
                FollowUpEntryContent::fromString($content),
                new DateTimeImmutable(),
            ),
            100,
        ));
    }

    private function countJobs(string $kind): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM reporter_notification_outbox WHERE kind = ?',
            [$kind],
        );
    }

    private function createPersistedReport(): Report
    {
        $organisationRepository = new DoctrineOrganisationRepository($this->entityManager);
        $reportRepository = new DoctrineReportRepository($this->entityManager);
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Horizonte',
            PublicReportingIdentifier::generate(),
        );
        $organisationRepository->save($organisation);
        $creation = Report::create(
            $organisation,
            SituationDescription::fromString('A fictional situation for notification tests.'),
            SituationContext::Digital,
        );
        $reportRepository->save($creation->report);

        return $creation->report;
    }
}
