<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Reporting\Domain\FollowUpEntryContent;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportFollowUpEntry;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Infrastructure\DoctrineReportFollowUpEntryRepository;
use App\Reporting\Infrastructure\DoctrineReportRepository;
use App\Reporting\Infrastructure\DoctrineReporterEmailNotifications;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class DoctrineReportFollowUpEntryRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineOrganisationRepository $organisationRepository;
    private DoctrineReportRepository $reportRepository;
    private DoctrineReportFollowUpEntryRepository $followUpEntryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = new DoctrineOrganisationRepository(
            $this->entityManager,
        );
        $this->reportRepository = new DoctrineReportRepository(
            $this->entityManager,
        );
        $this->followUpEntryRepository = new DoctrineReportFollowUpEntryRepository(
            $this->entityManager,
            new DoctrineReporterEmailNotifications($this->entityManager->getConnection()),
        );
    }

    public function testItSavesAndFindsEntriesOrderedByCreationTime(): void
    {
        $report = $this->createPersistedReport();

        $second = ReportFollowUpEntry::addedByReporter(
            $report,
            FollowUpEntryContent::fromString('Second entry.'),
            new DateTimeImmutable('2026-08-07T10:05:00+00:00'),
        );
        self::assertTrue(
            $this->followUpEntryRepository->saveIfReportHasCapacity($second, 100),
        );

        $first = ReportFollowUpEntry::addedByReporter(
            $report,
            FollowUpEntryContent::fromString('First entry.'),
            new DateTimeImmutable('2026-08-07T10:00:00+00:00'),
        );
        self::assertTrue(
            $this->followUpEntryRepository->saveIfReportHasCapacity($first, 100),
        );

        $this->entityManager->clear();
        $report = $this->reportRepository->findByPublicReference(
            $report->publicReference(),
        );
        self::assertInstanceOf(Report::class, $report);

        $entries = $this->followUpEntryRepository
            ->findByReportOrderedByCreatedAt($report, 100);

        self::assertCount(2, $entries);
        self::assertSame('First entry.', $entries[0]->content()->toString());
        self::assertSame('Second entry.', $entries[1]->content()->toString());
    }

    public function testItReturnsAnEmptyListWhenThereAreNoEntries(): void
    {
        $report = $this->createPersistedReport();

        $entries = $this->followUpEntryRepository
            ->findByReportOrderedByCreatedAt($report, 100);

        self::assertSame([], $entries);
    }

    public function testItOnlyReturnsEntriesForTheGivenReport(): void
    {
        $reportA = $this->createPersistedReport();
        $reportB = $this->createPersistedReport();

        $this->followUpEntryRepository->saveIfReportHasCapacity(
            ReportFollowUpEntry::addedByReporter(
                $reportA,
                FollowUpEntryContent::fromString('Belongs to report A.'),
                new DateTimeImmutable(),
            ),
            100,
        );

        $entries = $this->followUpEntryRepository
            ->findByReportOrderedByCreatedAt($reportB, 100);

        self::assertSame([], $entries);
    }

    public function testItDoesNotSaveBeyondTheReportCapacity(): void
    {
        $report = $this->createPersistedReport();

        $first = ReportFollowUpEntry::addedByReporter(
            $report,
            FollowUpEntryContent::fromString('First entry.'),
            new DateTimeImmutable(),
        );
        $second = ReportFollowUpEntry::addedByReporter(
            $report,
            FollowUpEntryContent::fromString('Second entry.'),
            new DateTimeImmutable(),
        );

        self::assertTrue(
            $this->followUpEntryRepository->saveIfReportHasCapacity($first, 1),
        );
        self::assertFalse(
            $this->followUpEntryRepository->saveIfReportHasCapacity($second, 1),
        );
        self::assertCount(
            1,
            $this->followUpEntryRepository
                ->findByReportOrderedByCreatedAt($report, 100),
        );
    }

    private function createPersistedReport(): Report
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Horizonte',
            PublicReportingIdentifier::generate(),
        );
        $this->organisationRepository->save($organisation);

        $creationResult = Report::create(
            $organisation,
            SituationDescription::fromString(
                'A situation has been observed during break time.',
            ),
            SituationContext::InPerson,
        );
        $this->reportRepository->save($creationResult->report);

        return $creationResult->report;
    }
}
