<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAccountAuditAction;
use App\Professionals\Domain\ProfessionalAccountAuditEvent;
use App\Professionals\Domain\ProfessionalAccountAuditEventRepository;
use App\Professionals\Domain\ProfessionalAccountStatus;
use App\Professionals\Domain\ProfessionalCredentialInvitation;
use App\Professionals\Domain\ProfessionalCredentialInvitationPurpose;
use App\Professionals\Domain\ProfessionalCredentialInvitationRepository;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRepository;
use App\Professionals\Domain\ProfessionalRole;
use DateInterval;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ManageProfessionalAccount
{
    private const CREDENTIAL_LIFETIME = 'PT24H';

    public function __construct(
        private ProfessionalRepository $professionals,
        private OrganisationMembershipRepository $memberships,
        private ProfessionalCredentialInvitationRepository $invitations,
        private ProfessionalAccountAuditEventRepository $auditEvents,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function invite(
        Organisation $organisation,
        string $name,
        ProfessionalEmail $email,
        ProfessionalRole $role,
        Professional $actor,
        DateTimeImmutable $now,
    ): ProfessionalCredentialResult {
        $this->requireAdministrator($organisation, $actor);
        if ($this->professionals->findByEmail($email) !== null) {
            throw new LogicException('A professional with this email already exists.');
        }

        $professional = new Professional(
            Uuid::v7(),
            self::name($name),
            $email,
            $now,
            active: false,
            accountStatus: ProfessionalAccountStatus::Invited,
        );
        $this->professionals->save($professional);
        $this->memberships->save(new OrganisationMembership(Uuid::v7(), $professional, $organisation, $role, $now));
        $this->audit($professional, $actor, ProfessionalAccountAuditAction::Invited, $now);

        return $this->issue($professional, $actor, ProfessionalCredentialInvitationPurpose::Activation, $now);
    }

    public function issuePasswordReset(Organisation $organisation, Professional $target, Professional $actor, DateTimeImmutable $now): ProfessionalCredentialResult
    {
        $this->requireAdministrator($organisation, $actor);
        if (!$this->memberships->hasActiveMembership($target, $organisation) || !$target->isActive()) {
            throw new LogicException('The target professional is unavailable in this organisation.');
        }

        $result = $this->issue($target, $actor, ProfessionalCredentialInvitationPurpose::PasswordReset, $now);
        $this->audit($target, $actor, ProfessionalAccountAuditAction::PasswordResetIssued, $now);

        return $result;
    }

    public function suspend(Organisation $organisation, Professional $target, Professional $actor): void
    {
        $this->requireAdministrator($organisation, $actor);
        $this->requireManagedProfessional($organisation, $target, $actor);
        $target->suspend();
        $this->professionals->save($target);
        $this->audit($target, $actor, ProfessionalAccountAuditAction::Suspended, new DateTimeImmutable());
    }

    public function reactivate(Organisation $organisation, Professional $target, Professional $actor): void
    {
        $this->requireAdministrator($organisation, $actor);
        $this->requireManagedProfessional($organisation, $target, $actor);
        $target->reactivate();
        $this->professionals->save($target);
        $this->audit($target, $actor, ProfessionalAccountAuditAction::Reactivated, new DateTimeImmutable());
    }

    public function deactivate(Organisation $organisation, Professional $target, Professional $actor): void
    {
        $this->requireAdministrator($organisation, $actor);
        $this->requireManagedProfessional($organisation, $target, $actor);
        $target->deactivate();
        $this->professionals->save($target);
        $this->audit($target, $actor, ProfessionalAccountAuditAction::Deactivated, new DateTimeImmutable());
    }

    public function acceptCredential(string $secret, string $password, DateTimeImmutable $now): bool
    {
        $invitation = $this->invitations->findBySecret($secret);
        if (!$invitation instanceof ProfessionalCredentialInvitation || !$invitation->accepts($secret, $now)) {
            return false;
        }

        $professional = $invitation->professional();
        if ($invitation->purpose() === ProfessionalCredentialInvitationPurpose::Activation) {
            if ($professional->accountStatus() !== ProfessionalAccountStatus::Invited) {
                return false;
            }
            $professional->activate($this->passwordHasher->hashPassword($professional, $password));
        } elseif (!$professional->isActive()) {
            return false;
        } else {
            $professional->replacePasswordHash($this->passwordHasher->hashPassword($professional, $password));
        }
        $invitation->useAt($now);
        $this->professionals->save($professional);
        $this->invitations->save($invitation);
        $this->audit($professional, $professional, ProfessionalAccountAuditAction::CredentialAccepted, $now);

        return true;
    }

    private function requireAdministrator(Organisation $organisation, Professional $actor): void
    {
        if (!$actor->isActive() || $this->memberships->findActiveByProfessionalAndOrganisation($actor, $organisation, ProfessionalRole::Administrator) === null) {
            throw new LogicException('The professional cannot administer this organisation.');
        }
    }

    private function requireManagedProfessional(Organisation $organisation, Professional $target, Professional $actor): void
    {
        if ($target->id()->equals($actor->id()) || !$this->memberships->hasActiveMembership($target, $organisation)) {
            throw new LogicException('The target professional is unavailable in this organisation.');
        }
    }

    private function issue(Professional $professional, Professional $actor, ProfessionalCredentialInvitationPurpose $purpose, DateTimeImmutable $now): ProfessionalCredentialResult
    {
        $secret = bin2hex(random_bytes(32));
        $expiresAt = $now->add(new DateInterval(self::CREDENTIAL_LIFETIME));
        $this->invitations->save(new ProfessionalCredentialInvitation(Uuid::v7(), $professional, $actor, $purpose, $secret, $expiresAt));

        return new ProfessionalCredentialResult($professional, $secret, $expiresAt);
    }

    private function audit(Professional $target, Professional $actor, ProfessionalAccountAuditAction $action, DateTimeImmutable $now): void
    {
        $this->auditEvents->append(new ProfessionalAccountAuditEvent(Uuid::v7(), $target, $actor, $action, $now));
    }

    private static function name(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 160) {
            throw new \InvalidArgumentException('Professional name must contain between 1 and 160 characters.');
        }

        return $name;
    }
}
