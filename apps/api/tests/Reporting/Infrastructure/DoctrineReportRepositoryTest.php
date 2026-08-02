<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Infrastructure\DoctrineReportRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use Symfony\Component\Uid\Uuid;

final class DoctrineReportRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineOrganisationRepository $organisationRepository;
    private DoctrineReportRepository $reportRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = new DoctrineOrganisationRepository(
            $this->entityManager,
        );
        $this->reportRepository = new DoctrineReportRepository(
            $this->entityManager,
        );
    }

    public function testItSavesAndFindsAReportByPublicReference(): void
    {
        $organisation = $this->createOrganisation();
        $this->organisationRepository->save($organisation);

        $creationResult = Report::create(
            $organisation,
            'A student is receiving threatening messages.',
            SituationContext::Digital,
        );
        $report = $creationResult->report;

        $this->reportRepository->save($report);
        $this->entityManager->clear();

        $persistedReport = $this->reportRepository->findByPublicReference(
            $report->publicReference(),
        );

        self::assertNotNull($persistedReport);
        self::assertNotSame($report, $persistedReport);
        self::assertSame(
            $report->id()->toRfc4122(),
            $persistedReport->id()->toRfc4122(),
        );
        self::assertSame(
            $organisation->id()->toRfc4122(),
            $persistedReport->organisation()->id()->toRfc4122(),
        );
        self::assertSame(
            'A student is receiving threatening messages.',
            $persistedReport->situationDescription(),
        );
        self::assertSame(
            SituationContext::Digital,
            $persistedReport->situationContext(),
        );
        self::assertSame(ReportStatus::Received, $persistedReport->status());
        self::assertSame(
            $report->publicReference(),
            $persistedReport->publicReference(),
        );
        self::assertSame(
            $report->createdAt()->getTimestamp(),
            $persistedReport->createdAt()->getTimestamp(),
        );
        self::assertTrue(
            $persistedReport->verifyAccessSecret(
                $creationResult->plainAccessSecret,
            ),
        );
    }

    public function testItStoresOnlyAOneWayRepresentationOfTheAccessSecret(): void
    {
        $organisation = $this->createOrganisation();
        $this->organisationRepository->save($organisation);

        $creationResult = Report::create(
            $organisation,
            'A situation has been observed during break time.',
            SituationContext::InPerson,
        );
        $this->reportRepository->save($creationResult->report);

        $storedSecret = $this->entityManager
            ->getConnection()
            ->fetchOne(
                <<<'SQL'
                    SELECT access_secret_hash
                    FROM reports
                    WHERE public_reference = :publicReference
                    SQL,
                [
                    'publicReference' => $creationResult->report->publicReference(),
                ],
            );

        self::assertIsString($storedSecret);
        self::assertNotSame(
            $creationResult->plainAccessSecret,
            $storedSecret,
        );
        self::assertSame(
            hash('sha256', $creationResult->plainAccessSecret),
            $storedSecret,
        );
    }

    public function testItReturnsNullWhenTheReportDoesNotExist(): void
    {
        $report = $this->reportRepository->findByPublicReference(
            'REFERENCE-THAT-DOES-NOT-EXIST',
        );

        self::assertNull($report);
    }

    private function createOrganisation(): Organisation
    {
        return new Organisation(
            Uuid::v7(),
            'IES Horizonte',
            PublicReportingIdentifier::generate(),
        );
    }
}
