<?php

declare(strict_types=1);

namespace App\Tests\Cases\Infrastructure;

use App\Cases\Domain\CaseModality;
use App\Cases\Domain\CaseProtocolStage;
use App\Cases\Domain\CaseTask;
use App\Cases\Domain\CaseTaskKind;
use App\Cases\Domain\ManagedCase;
use App\Cases\Domain\WorkflowSourceAuthority;
use App\Cases\Domain\WorkflowSourceVersion;
use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class CaseTaskPersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        parent::tearDown();
    }

    public function testSourceAwareTaskRoundTripsWithItsLifecycleEvidence(): void
    {
        $professional = new Professional(
            Uuid::v7(),
            'Fictional Persistence Professional',
            ProfessionalEmail::fromString(Uuid::v7()->toRfc4122().'@example.invalid'),
            new DateTimeImmutable('2026-08-11T08:00:00+00:00'),
        );
        $organisation = new Organisation(
            Uuid::v7(),
            'Fictional Persistence School',
            PublicReportingIdentifier::generate(),
        );
        $managedCase = new ManagedCase(
            Uuid::v7(),
            $organisation,
            $professional,
            new DateTimeImmutable('2026-08-11T09:00:00+00:00'),
            CaseModality::Mixed,
        );
        $source = new WorkflowSourceVersion(
            Uuid::v7(),
            'TEST-INTERNAL-'.Uuid::v7()->toRfc4122(),
            '2026-08-11',
            'Fictional internal persistence source',
            null,
            'ES-AN-GR',
            WorkflowSourceAuthority::Internal,
            new DateTimeImmutable('2026-08-11'),
            new DateTimeImmutable('2026-08-11'),
        );
        $taskId = Uuid::v7();
        $task = new CaseTask(
            $taskId,
            $managedCase,
            $professional,
            $source,
            CaseProtocolStage::InformationCollection,
            CaseTaskKind::InternalAction,
            'Review fictional digital evidence',
            new DateTimeImmutable('2026-08-12T10:00:00+00:00'),
            $professional,
            new DateTimeImmutable('2026-08-11T10:00:00+00:00'),
        );
        $task->markNotApplicable(
            $professional,
            new DateTimeImmutable('2026-08-11T11:00:00+00:00'),
            'The fictional case contains no digital evidence.',
        );

        $this->entityManager->persist($organisation);
        $this->entityManager->persist($professional);
        $this->entityManager->persist($managedCase);
        $this->entityManager->persist($source);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->entityManager->find(CaseTask::class, $taskId);
        self::assertInstanceOf(CaseTask::class, $stored);
        self::assertSame('Review fictional digital evidence', $stored->title());
        self::assertSame(WorkflowSourceAuthority::Internal, $stored->source()->authority());
        self::assertSame('The fictional case contains no digital evidence.', $stored->notApplicableReason());
        self::assertSame($professional->id()->toRfc4122(), $stored->owner()->id()->toRfc4122());
    }
}
