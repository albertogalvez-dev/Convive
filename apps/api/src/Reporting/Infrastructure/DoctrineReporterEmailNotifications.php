<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReporterEmailAddress;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineReporterEmailNotifications
{
    public function __construct(private Connection $connection)
    {
    }

    public function status(Report $report): string
    {
        $status = $this->connection->fetchOne(
            'SELECT status FROM reporter_email_contacts WHERE report_id = ?',
            [$report->id()->toRfc4122()],
        );

        return is_string($status) ? $status : 'none';
    }

    public function configure(
        Report $report,
        ReporterEmailAddress $email,
        string $noticeVersion,
    ): string {
        return $this->connection->transactional(function () use ($report, $email, $noticeVersion): string {
            $now = DateTimeImmutable::createFromTimestamp(microtime(true));
            $existing = $this->connection->fetchAssociative(
                'SELECT id, email, status FROM reporter_email_contacts WHERE report_id = ? FOR UPDATE',
                [$report->id()->toRfc4122()],
            );

            if (
                $existing !== false
                && $existing['email'] === $email->toString()
                && $existing['status'] === 'verified'
            ) {
                return 'verified';
            }

            $contactId = $existing === false
                ? Uuid::v7()->toRfc4122()
                : (string) $existing['id'];

            $this->connection->executeStatement(
                <<<'SQL'
INSERT INTO reporter_email_contacts (
    id, report_id, email, status, consent_notice_version, consented_at,
    verification_token_hash, verification_expires_at, verified_at, created_at, updated_at
) VALUES (?, ?, ?, 'pending', ?, ?, NULL, NULL, NULL, ?, ?)
ON CONFLICT (report_id) DO UPDATE SET
    email = EXCLUDED.email,
    status = 'pending',
    consent_notice_version = EXCLUDED.consent_notice_version,
    consented_at = EXCLUDED.consented_at,
    verification_token_hash = NULL,
    verification_expires_at = NULL,
    verified_at = NULL,
    updated_at = EXCLUDED.updated_at
SQL,
                [
                    $contactId,
                    $report->id()->toRfc4122(),
                    $email->toString(),
                    $noticeVersion,
                    $now,
                    $now,
                    $now,
                ],
                [4 => 'datetime_immutable', 5 => 'datetime_immutable', 6 => 'datetime_immutable'],
            );

            $this->connection->executeStatement(
                "DELETE FROM reporter_notification_outbox WHERE contact_id = ? AND status <> 'delivered'",
                [$contactId],
            );
            $this->connection->insert('reporter_notification_outbox', [
                'id' => Uuid::v7()->toRfc4122(),
                'contact_id' => $contactId,
                'kind' => 'verification',
                'deduplication_key' => 'verification:'.Uuid::v7()->toRfc4122(),
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => $now,
                'created_at' => $now,
            ], [
                'available_at' => 'datetime_immutable',
                'created_at' => 'datetime_immutable',
            ]);

            return 'pending';
        });
    }

    public function remove(Report $report): void
    {
        $this->connection->executeStatement(
            'DELETE FROM reporter_email_contacts WHERE report_id = ?',
            [$report->id()->toRfc4122()],
        );
    }

    public function verify(string $plainToken): bool
    {
        if (!preg_match('/^[0-9a-f]{64}$/D', $plainToken)) {
            return false;
        }

        $now = DateTimeImmutable::createFromTimestamp(microtime(true));

        return $this->connection->executeStatement(
            <<<'SQL'
UPDATE reporter_email_contacts
SET status = 'verified', verified_at = ?, verification_token_hash = NULL,
    verification_expires_at = NULL, updated_at = ?
WHERE status = 'pending' AND verification_token_hash = ? AND verification_expires_at >= ?
SQL,
            [$now, $now, hash('sha256', $plainToken), $now],
            [0 => 'datetime_immutable', 1 => 'datetime_immutable', 3 => 'datetime_immutable'],
        ) === 1;
    }

    public function queueReportUpdate(Report $report, Uuid $entryId): void
    {
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO reporter_notification_outbox (
    id, contact_id, kind, deduplication_key, status, attempts, available_at, created_at
)
SELECT ?, id, 'report_update', ?, 'pending', 0, ?, ?
FROM reporter_email_contacts
WHERE report_id = ? AND status = 'verified'
ON CONFLICT (deduplication_key) DO NOTHING
SQL,
            [
                Uuid::v7()->toRfc4122(),
                'report-update:'.$entryId->toRfc4122(),
                $now,
                $now,
                $report->id()->toRfc4122(),
            ],
            [2 => 'datetime_immutable', 3 => 'datetime_immutable'],
        );
    }

    public function claim(): ?ReporterEmailDelivery
    {
        return $this->connection->transactional(function (): ?ReporterEmailDelivery {
            $now = DateTimeImmutable::createFromTimestamp(microtime(true));
            $stale = $now->sub(new DateInterval('PT5M'));
            $row = $this->connection->fetchAssociative(
                <<<'SQL'
SELECT outbox.id, outbox.contact_id, outbox.kind, outbox.attempts, contact.email
FROM reporter_notification_outbox outbox
JOIN reporter_email_contacts contact ON contact.id = outbox.contact_id
WHERE outbox.attempts < 3
  AND (
    (outbox.status = 'pending' AND outbox.available_at <= ?)
    OR (outbox.status = 'processing' AND outbox.processing_at < ?)
  )
ORDER BY outbox.created_at, outbox.id
LIMIT 1
FOR UPDATE OF outbox SKIP LOCKED
SQL,
                [$now, $stale],
                [0 => 'datetime_immutable', 1 => 'datetime_immutable'],
            );

            if ($row === false) {
                return null;
            }

            $attempt = (int) $row['attempts'] + 1;
            $this->connection->update('reporter_notification_outbox', [
                'status' => 'processing',
                'attempts' => $attempt,
                'processing_at' => $now,
            ], ['id' => $row['id']], ['processing_at' => 'datetime_immutable']);

            return new ReporterEmailDelivery(
                Uuid::fromString((string) $row['id']),
                Uuid::fromString((string) $row['contact_id']),
                (string) $row['email'],
                (string) $row['kind'],
                $attempt,
            );
        });
    }

    public function prepareVerificationToken(Uuid $contactId): string
    {
        $token = bin2hex(random_bytes(32));
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $expiresAt = $now->add(new DateInterval('PT24H'));
        $updated = $this->connection->executeStatement(
            <<<'SQL'
UPDATE reporter_email_contacts
SET verification_token_hash = ?, verification_expires_at = ?, updated_at = ?
WHERE id = ? AND status = 'pending'
SQL,
            [hash('sha256', $token), $expiresAt, $now, $contactId->toRfc4122()],
            [1 => 'datetime_immutable', 2 => 'datetime_immutable'],
        );

        if ($updated !== 1) {
            throw new \RuntimeException('The reporter email contact is no longer pending verification.');
        }

        return $token;
    }

    public function markDelivered(Uuid $deliveryId): void
    {
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $this->connection->update('reporter_notification_outbox', [
            'status' => 'delivered',
            'processing_at' => null,
            'completed_at' => $now,
        ], ['id' => $deliveryId->toRfc4122()], ['completed_at' => 'datetime_immutable']);
    }

    public function markFailed(ReporterEmailDelivery $delivery): void
    {
        $terminal = $delivery->attempt >= 3;
        $availableAt = DateTimeImmutable::createFromTimestamp(microtime(true))
            ->add(new DateInterval(match ($delivery->attempt) {
                1 => 'PT1M',
                2 => 'PT5M',
                default => 'PT15M',
            }));

        $this->connection->update('reporter_notification_outbox', [
            'status' => $terminal ? 'failed' : 'pending',
            'processing_at' => null,
            'available_at' => $availableAt,
            'completed_at' => $terminal ? $availableAt : null,
        ], ['id' => $delivery->id->toRfc4122()], [
            'available_at' => 'datetime_immutable',
            'completed_at' => 'datetime_immutable',
        ]);
    }

    /** @return array{contacts: int, deliveries: int} */
    public function purgeExpired(): array
    {
        $now = DateTimeImmutable::createFromTimestamp(microtime(true));
        $contactCutoff = $now->sub(new DateInterval('PT24H'));
        $deliveryCutoff = $now->sub(new DateInterval('P30D'));

        return $this->connection->transactional(function () use ($contactCutoff, $deliveryCutoff): array {
            $contacts = $this->connection->executeStatement(
                <<<'SQL'
DELETE FROM reporter_email_contacts
WHERE status = 'pending'
  AND updated_at < ?
SQL,
                [$contactCutoff],
                [0 => 'datetime_immutable'],
            );
            $deliveries = $this->connection->executeStatement(
                <<<'SQL'
DELETE FROM reporter_notification_outbox
WHERE status IN ('delivered', 'failed')
  AND completed_at < ?
SQL,
                [$deliveryCutoff],
                [0 => 'datetime_immutable'],
            );

            return ['contacts' => (int) $contacts, 'deliveries' => (int) $deliveries];
        });
    }
}
