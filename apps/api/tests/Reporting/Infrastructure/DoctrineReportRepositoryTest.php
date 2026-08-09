<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessSecret;
use App\Reporting\Domain\ReportStatus;
use App\Reporting\Domain\ReportReviewReason;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Infrastructure\DoctrineReportRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\OptimisticLockException;
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

        $description = SituationDescription::fromString(
            'A student is receiving threatening messages.',
        );

        $creationResult = Report::create(
            $organisation,
            $description,
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
        self::assertTrue(
            $description->equals(
                $persistedReport->situationDescription(),
            ),
        );
        self::assertSame(
            SituationContext::Digital,
            $persistedReport->situationContext(),
        );
        self::assertSame(
            ReportStatus::Received,
            $persistedReport->status(),
        );
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
            SituationDescription::fromString(
                'A situation has been observed during break time.',
            ),
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
                    'publicReference' => $creationResult
                        ->report
                        ->publicReference(),
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

    public function testItFindsAReportByItsAccessSecret(): void
    {
        $organisation = $this->createOrganisation();
        $this->organisationRepository->save($organisation);

        $creationResult = Report::create(
            $organisation,
            SituationDescription::fromString(
                'A situation was reported near the cafeteria.',
            ),
            SituationContext::InPerson,
        );
        $this->reportRepository->save($creationResult->report);
        $this->entityManager->clear();

        $foundReport = $this->reportRepository->findByAccessSecret(
            ReportAccessSecret::fromString(
                $creationResult->plainAccessSecret,
            ),
        );

        self::assertNotNull($foundReport);
        self::assertSame(
            $creationResult->report->id()->toRfc4122(),
            $foundReport->id()->toRfc4122(),
        );
    }

    public function testItReturnsNullWhenNoReportMatchesTheAccessSecret(): void
    {
        $foundReport = $this->reportRepository->findByAccessSecret(
            ReportAccessSecret::generate(),
        );

        self::assertNull($foundReport);
    }

    public function testConcurrentReviewCannotOverwriteTheFirstPersistedTransition(): void
    {
        $organisation = $this->createOrganisation();
        $this->organisationRepository->save($organisation);
        $report = Report::create(
            $organisation,
            SituationDescription::fromString('A concurrency-safe fictional report.'),
            SituationContext::Unknown,
        )->report;
        $this->reportRepository->save($report);
        $this->entityManager->clear();

        $staleReport = $this->reportRepository->findByPublicReference(
            $report->publicReference(),
        );
        self::assertNotNull($staleReport);
        $staleReport->review(
            ReportReviewReason::fromString('A stale concurrent assessment must not win.'),
            Uuid::v7(),
            new DateTimeImmutable(),
        );

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE reports SET version = version + 1 WHERE id = ?',
            [$staleReport->id()->toRfc4122()],
        );

        $this->expectException(OptimisticLockException::class);

        $this->reportRepository->save($staleReport);
    }

    public function testItRejectsANonCanonicalAccessSecretHash(): void
    {
        $organisation = $this->createOrganisation();
        $this->organisationRepository->save($organisation);

        $this->expectException(DbalException::class);
        $this->expectExceptionMessageMatches(
            '/chk_reports_access_secret_hash_format/i',
        );

        $this->insertReportRow(
            $organisation->id(),
            'NOT-A-CANONICAL-LOWERCASE-HEXADECIMAL-HASH',
        );
    }

    public function testItRejectsADuplicateAccessSecretHash(): void
    {
        $organisation = $this->createOrganisation();
        $this->organisationRepository->save($organisation);

        $creationResult = Report::create(
            $organisation,
            SituationDescription::fromString(
                'A situation has been observed near the entrance.',
            ),
            SituationContext::Unknown,
        );
        $this->reportRepository->save($creationResult->report);

        $duplicateHash = $this->entityManager
            ->getConnection()
            ->fetchOne(
                <<<'SQL'
                    SELECT access_secret_hash
                    FROM reports
                    WHERE public_reference = :publicReference
                    SQL,
                [
                    'publicReference' => $creationResult
                        ->report
                        ->publicReference(),
                ],
            );

        $this->expectException(DbalException::class);
        $this->expectExceptionMessageMatches(
            '/uniq_f11fa745290525a3/i',
        );

        $this->insertReportRow($organisation->id(), $duplicateHash);
    }

    private function insertReportRow(
        Uuid $organisationId,
        string $accessSecretHash,
    ): void {
        $this->entityManager
            ->getConnection()
            ->executeStatement(
                <<<'SQL'
                    INSERT INTO reports (
                        id,
                        organisation_id,
                        situation_description,
                        situation_context,
                        status,
                        public_reference,
                        access_secret_hash,
                        created_at
                    ) VALUES (
                        :id,
                        :organisationId,
                        :situationDescription,
                        :situationContext,
                        :status,
                        :publicReference,
                        :accessSecretHash,
                        :createdAt
                    )
                    SQL,
                [
                    'id' => Uuid::v7()->toRfc4122(),
                    'organisationId' => $organisationId->toRfc4122(),
                    'situationDescription' => 'A raw integration-test row.',
                    'situationContext' => SituationContext::Unknown->value,
                    'status' => ReportStatus::Received->value,
                    'publicReference' => bin2hex(random_bytes(10)),
                    'accessSecretHash' => $accessSecretHash,
                    'createdAt' => (new DateTimeImmutable())->format(
                        DateTimeImmutable::ATOM,
                    ),
                ],
            );
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
