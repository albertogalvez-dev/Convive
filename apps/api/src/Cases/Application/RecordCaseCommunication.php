<?php

declare(strict_types=1);

namespace App\Cases\Application;

use App\Cases\Domain\CaseAuditAction;
use App\Cases\Domain\CaseAuditEvent;
use App\Cases\Domain\CaseAuditEventRepository;
use App\Cases\Domain\CaseAuditTarget;
use App\Cases\Domain\CaseCommunication;
use App\Cases\Domain\CaseCommunicationChannel;
use App\Cases\Domain\CaseCommunicationRecipient;
use App\Cases\Domain\CaseCommunicationRepository;
use App\Cases\Domain\CaseCommunicationStatus;
use App\Cases\Domain\CasePermission;
use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class RecordCaseCommunication
{
    public function __construct(
        private AuthoriseCaseAccess $authorise,
        private CaseCommunicationRepository $communications,
        private CaseAuditEventRepository $auditEvents,
    ) {
    }

    public function record(
        Uuid $id,
        ManagedCase $managedCase,
        Professional $responsible,
        CaseCommunicationRecipient $recipient,
        CaseCommunicationChannel $channel,
        CaseCommunicationStatus $status,
        DateTimeImmutable $occurredAt,
        string $note,
        Professional $actor,
        DateTimeImmutable $now,
        ?CaseCommunication $supersedes = null,
    ): CaseCommunication {
        $this->authorise->require($managedCase, $actor, CasePermission::Manage);
        $this->authorise->require($managedCase, $responsible, CasePermission::View);

        $communication = new CaseCommunication($id, $managedCase, $responsible, $recipient, $channel, $status, $occurredAt, $note, $actor, $now, $supersedes);
        $this->auditEvents->append(new CaseAuditEvent(
            Uuid::v7(),
            $managedCase,
            $actor,
            $supersedes === null ? CaseAuditAction::CommunicationRecorded : CaseAuditAction::CommunicationCorrected,
            CaseAuditTarget::Communication,
            $communication->id(),
            $now,
        ));
        $this->communications->save($communication);

        return $communication;
    }
}
