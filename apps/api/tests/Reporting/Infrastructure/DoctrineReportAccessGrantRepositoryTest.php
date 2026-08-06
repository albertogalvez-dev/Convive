<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Infrastructure\DoctrineOrganisationRepository;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Infrastructure\DoctrineReportAccessGrantRepository;
use App\Reporting\Infrastructure\DoctrineReportRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Uid\Uuid;

final class DoctrineReportAccessGrantRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineOrganisationRepository $organisationRepository;
    private DoctrineReportRepository $reportRepository;
    private DoctrineReportAccessGrantRepository $grantRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = new DoctrineOrganisationRepository(
            $this->entityManager,
        );
        $this->reportRepository = new DoctrineReportRepository(
            $this->entityManager,
        );
        $this->grantRepository = new DoctrineReportAccessGrantRepository(
            $this->entityManager,
        );
    }

    public function testItSavesAndFindsAGrantByItsCapability(): void
    {
        $report = $this->createPersistedReport();
        $capability = ReportAccessCapability::generate();

        $grant = ReportAccessGrant::issue(
            $report,
            $capability,
            new DateTimeImmutable('2026-08-06T10:00:00+00:00'),
        );
        $this->grantRepository->save($grant);
        $this->entityManager->clear();

        $foundGrant = $this->grantRepository->findByCapability($capability);

        self::assertNotNull($foundGrant);
        self::assertSame(
            $grant->id()->toRfc4122(),
            $foundGrant->id()->toRfc4122(),
        );
        self::assertSame(
            $report->id()->toRfc4122(),
            $foundGrant->report()->id()->toRfc4122(),
        );
    }

    public function testItReturnsNullWhenNoGrantMatchesTheCapability(): void
    {
        $foundGrant = $this->grantRepository->findByCapability(
            ReportAccessCapability::generate(),
        );

        self::assertNull($foundGrant);
    }

    public function testItRejectsANonCanonicalCapabilityHash(): void
    {
        $report = $this->createPersistedReport();

        $this->expectException(DbalException::class);
        $this->expectExceptionMessageMatches(
            '/chk_report_access_grants_capability_hash_format/i',
        );

        $this->insertGrantRow($report->id(), 'NOT-A-CANONICAL-HASH');
    }

    public function testItRejectsADuplicateCapabilityHash(): void
    {
        $report = $this->createPersistedReport();
        $capability = ReportAccessCapability::generate();

        $grant = ReportAccessGrant::issue(
            $report,
            $capability,
            new DateTimeImmutable('2026-08-06T10:00:00+00:00'),
        );
        $this->grantRepository->save($grant);

        $this->expectException(DbalException::class);
        $this->expectExceptionMessageMatches(
            '/uniq_1ffb1394b24d9a2d/i',
        );

        $this->insertGrantRow($report->id(), $capability->lookupHash());
    }

    private function insertGrantRow(
        Uuid $reportId,
        string $capabilityHash,
    ): void {
        $now = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        $this->entityManager
            ->getConnection()
            ->executeStatement(
                <<<'SQL'
                    INSERT INTO report_access_grants (
                        id,
                        report_id,
                        capability_hash,
                        issued_at,
                        last_used_at,
                        absolute_expires_at
                    ) VALUES (
                        :id,
                        :reportId,
                        :capabilityHash,
                        :issuedAt,
                        :issuedAt,
                        :absoluteExpiresAt
                    )
                    SQL,
                [
                    'id' => Uuid::v7()->toRfc4122(),
                    'reportId' => $reportId->toRfc4122(),
                    'capabilityHash' => $capabilityHash,
                    'issuedAt' => $now,
                    'absoluteExpiresAt' => $now,
                ],
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
