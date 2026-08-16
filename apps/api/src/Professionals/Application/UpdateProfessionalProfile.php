<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAccountAuditAction;
use App\Professionals\Domain\ProfessionalAccountAuditEvent;
use App\Professionals\Domain\ProfessionalAccountAuditEventRepository;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalEmailAlreadyUsed;
use App\Professionals\Domain\ProfessionalRepository;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class UpdateProfessionalProfile
{
    public function __construct(
        private ProfessionalRepository $professionals,
        private ProfessionalAccountAuditEventRepository $auditEvents,
    ) {
    }

    /**
     * Self-service profile changes. A professional can only ever act on their
     * own account here: role, organisation membership and case access are
     * administrator-controlled and are not reachable through this command.
     *
     * Both changes are audited with the professional as target and actor, so an
     * account's own history distinguishes a self-service correction from an
     * administrator action. The email change is the security-relevant one: it
     * replaces the login identifier and ends every session.
     *
     * @throws ProfessionalEmailAlreadyUsed when the address belongs to someone else
     */
    public function update(
        Professional $professional,
        string $name,
        ProfessionalEmail $email,
        DateTimeImmutable $now,
    ): void {
        $existing = $this->professionals->findByEmail($email);
        if ($existing !== null && !$existing->id()->equals($professional->id())) {
            throw new ProfessionalEmailAlreadyUsed();
        }

        $nameChanged = $professional->name() !== trim($name);
        $emailChanged = !$professional->email()->equals($email);

        $professional->rename($name);
        $professional->changeEmail($email);
        $this->professionals->save($professional);

        foreach ([
            [$nameChanged, ProfessionalAccountAuditAction::ProfileNameChanged],
            [$emailChanged, ProfessionalAccountAuditAction::ProfileEmailChanged],
        ] as [$changed, $action]) {
            if ($changed) {
                $this->auditEvents->append(new ProfessionalAccountAuditEvent(
                    Uuid::v7(),
                    $professional,
                    $professional,
                    $action,
                    $now,
                ));
            }
        }
    }
}
