<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportAttachmentStatus;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineReportAttachmentRepository implements ReportAttachmentRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(ReportAttachment $attachment): void
    {
        $this->entityManager->persist($attachment);
        $this->entityManager->flush();
    }

    public function saveQuarantinedWithReportCapacity(array $attachments): void
    {
        if ($attachments === []) {
            throw new InvalidArgumentException('At least one attachment is required.');
        }

        $this->entityManager->wrapInTransaction(function () use ($attachments): void {
            $report = $attachments[0]->report();

            foreach ($attachments as $attachment) {
                if (!$attachment->report()->id()->equals($report->id())) {
                    throw new InvalidArgumentException('All attachments must belong to the same report.');
                }
            }

            $this->entityManager->refresh($report, LockMode::PESSIMISTIC_WRITE);

            $report->reserveAttachmentCapacity(
                count($attachments),
                array_sum(array_map(
                    static fn (ReportAttachment $attachment): int => $attachment->byteSize(),
                    $attachments,
                )),
            );

            foreach ($attachments as $attachment) {
                $this->entityManager->persist($attachment);
            }
        });
    }

    public function findByIdForReport(Uuid $id, Report $report): ?ReportAttachment
    {
        /** @var ReportAttachment|null */
        return $this->entityManager
            ->getRepository(ReportAttachment::class)
            ->findOneBy(['id' => $id, 'report' => $report]);
    }

    public function findByReport(Report $report): array
    {
        /** @var list<ReportAttachment> */
        return $this->entityManager
            ->createQueryBuilder()
            ->select('attachment')
            ->from(ReportAttachment::class, 'attachment')
            ->where('attachment.report = :report')
            ->setParameter('report', $report)
            ->orderBy('attachment.createdAt', 'ASC')
            ->addOrderBy('attachment.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAwaitingScan(int $limit): array
    {
        /** @var list<ReportAttachment> */
        return $this->entityManager
            ->createQueryBuilder()
            ->select('attachment')
            ->from(ReportAttachment::class, 'attachment')
            ->where('attachment.status IN (:statuses)')
            ->setParameter('statuses', [
                ReportAttachmentStatus::Quarantined->value,
                ReportAttachmentStatus::Scanning->value,
            ])
            ->orderBy('attachment.createdAt', 'ASC')
            ->addOrderBy('attachment.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findForCleanup(
        DateTimeImmutable $quarantineDeadline,
        DateTimeImmutable $availableDeadline,
        int $limit,
    ): array {
        /** @var list<ReportAttachment> */
        return $this->entityManager
            ->createQueryBuilder()
            ->select('attachment')
            ->from(ReportAttachment::class, 'attachment')
            ->where('attachment.status IN (:deletableStatuses)')
            ->orWhere('attachment.status IN (:quarantineStatuses) AND attachment.createdAt <= :quarantineDeadline')
            ->orWhere('attachment.status = :availableStatus AND attachment.createdAt <= :availableDeadline')
            ->setParameter('deletableStatuses', [
                ReportAttachmentStatus::Rejected->value,
                ReportAttachmentStatus::DeletionPending->value,
            ])
            ->setParameter('quarantineStatuses', [
                ReportAttachmentStatus::Quarantined->value,
                ReportAttachmentStatus::Scanning->value,
            ])
            ->setParameter('quarantineDeadline', $quarantineDeadline)
            ->setParameter('availableStatus', ReportAttachmentStatus::Available->value)
            ->setParameter('availableDeadline', $availableDeadline)
            ->orderBy('attachment.createdAt', 'ASC')
            ->addOrderBy('attachment.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
