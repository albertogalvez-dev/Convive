<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Application;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Application\ManageProfessionalAccount;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\OrganisationMembershipRepository;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalAccountAuditEvent;
use App\Professionals\Domain\ProfessionalAccountAuditEventRepository;
use App\Professionals\Domain\ProfessionalAccountStatus;
use App\Professionals\Domain\ProfessionalCredentialInvitation;
use App\Professionals\Domain\ProfessionalCredentialInvitationRepository;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRepository;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

final class ManageProfessionalAccountTest extends TestCase
{
    public function testAdministratorCanInviteAndActivateAProfessionalExactlyOnce(): void
    {
        [$organisation, $administrator, $memberships] = $this->administratorScope();
        $professionals = new InMemoryProfessionals([$administrator]);
        $invitations = new InMemoryCredentialInvitations();
        $accounts = $this->accounts($professionals, $memberships, $invitations);
        $now = new DateTimeImmutable('2026-08-14T08:00:00+00:00');

        $result = $accounts->invite($organisation, 'Fictional new professional', ProfessionalEmail::fromString('new-professional@example.invalid'), ProfessionalRole::Triage, $administrator, $now);

        self::assertSame(ProfessionalAccountStatus::Invited, $result->professional->accountStatus());
        self::assertFalse($result->professional->isActive());
        self::assertTrue($accounts->acceptCredential($result->secret, 'fictional secure password', $now->modify('+1 hour')));
        self::assertSame(ProfessionalAccountStatus::Active, $result->professional->accountStatus());
        self::assertTrue($result->professional->isActive());
        self::assertFalse($accounts->acceptCredential($result->secret, 'another fictional password', $now->modify('+2 hours')));
    }

    public function testNonAdministratorCannotInvite(): void
    {
        [$organisation, $administrator, $memberships] = $this->administratorScope();
        $triage = $this->professional('triage');
        $memberships->save(new OrganisationMembership(Uuid::v7(), $triage, $organisation, ProfessionalRole::Triage, new DateTimeImmutable()));
        $professionals = new InMemoryProfessionals([$administrator, $triage]);
        $accounts = $this->accounts($professionals, $memberships, new InMemoryCredentialInvitations());

        $this->expectException(LogicException::class);
        $accounts->invite($organisation, 'Fictional blocked professional', ProfessionalEmail::fromString('blocked@example.invalid'), ProfessionalRole::Triage, $triage, new DateTimeImmutable());
    }

    public function testExpiredActivationCredentialCannotChangeTheInvitedAccount(): void
    {
        [$organisation, $administrator, $memberships] = $this->administratorScope();
        $professionals = new InMemoryProfessionals([$administrator]);
        $accounts = $this->accounts($professionals, $memberships, new InMemoryCredentialInvitations());
        $now = new DateTimeImmutable('2026-08-14T08:00:00+00:00');
        $result = $accounts->invite($organisation, 'Fictional expired invitation', ProfessionalEmail::fromString('expired@example.invalid'), ProfessionalRole::Triage, $administrator, $now);

        self::assertFalse($accounts->acceptCredential($result->secret, 'fictional secure password', $now->modify('+24 hours')));
        self::assertSame(ProfessionalAccountStatus::Invited, $result->professional->accountStatus());
        self::assertFalse($result->professional->isActive());
    }

    public function testAdministratorCanSuspendAndReactivateAnotherCentreProfessional(): void
    {
        [$organisation, $administrator, $memberships] = $this->administratorScope();
        $target = $this->professional('target');
        $memberships->save(new OrganisationMembership(Uuid::v7(), $target, $organisation, ProfessionalRole::Triage, new DateTimeImmutable()));
        $accounts = $this->accounts(new InMemoryProfessionals([$administrator, $target]), $memberships, new InMemoryCredentialInvitations());
        $before = $target->securityRevision();

        $accounts->suspend($organisation, $target, $administrator);
        self::assertSame(ProfessionalAccountStatus::Suspended, $target->accountStatus());
        self::assertFalse($target->isActive());
        self::assertSame($before + 1, $target->securityRevision());

        $accounts->reactivate($organisation, $target, $administrator);
        self::assertSame(ProfessionalAccountStatus::Active, $target->accountStatus());
        self::assertTrue($target->isActive());
    }

    /** @return array{Organisation, Professional, InMemoryMemberships} */
    private function administratorScope(): array
    {
        $organisation = new Organisation(Uuid::v7(), 'Fictional School', PublicReportingIdentifier::generate());
        $administrator = $this->professional('administrator');
        $memberships = new InMemoryMemberships();
        $memberships->save(new OrganisationMembership(Uuid::v7(), $administrator, $organisation, ProfessionalRole::Administrator, new DateTimeImmutable()));

        return [$organisation, $administrator, $memberships];
    }

    private function accounts(InMemoryProfessionals $professionals, InMemoryMemberships $memberships, InMemoryCredentialInvitations $invitations): ManageProfessionalAccount
    {
        return new ManageProfessionalAccount($professionals, $memberships, $invitations, new InMemoryProfessionalAccountAuditEvents(), new InMemoryUserPasswordHasher());
    }

    private function professional(string $name): Professional
    {
        return new Professional(Uuid::v7(), 'Fictional '.$name, ProfessionalEmail::fromString($name.'-'.Uuid::v7()->toRfc4122().'@example.invalid'), new DateTimeImmutable());
    }
}

final class InMemoryProfessionals implements ProfessionalRepository
{
    /** @param list<Professional> $professionals */
    public function __construct(private array $professionals) {}
    public function find(Uuid $id): ?Professional { foreach ($this->professionals as $professional) if ($professional->id()->equals($id)) return $professional; return null; }
    public function save(Professional $professional): void { foreach ($this->professionals as $current) if ($current->id()->equals($professional->id())) return; $this->professionals[] = $professional; }
    public function findByEmail(ProfessionalEmail $email): ?Professional { foreach ($this->professionals as $professional) if ($professional->email()->equals($email)) return $professional; return null; }
}

final class InMemoryMemberships implements OrganisationMembershipRepository
{
    /** @var list<OrganisationMembership> */ private array $memberships = [];
    public function save(OrganisationMembership $membership): void { $this->memberships[] = $membership; }
    public function findActiveByProfessional(Professional $professional): array { return array_values(array_filter($this->memberships, static fn (OrganisationMembership $membership): bool => $membership->professional()->id()->equals($professional->id()) && $membership->isActive())); }
    public function hasActiveMembership(Professional $professional, Organisation $organisation): bool { foreach ($this->findActiveByProfessional($professional) as $membership) if ($membership->organisation()->id()->equals($organisation->id())) return true; return false; }
    public function findActiveByOrganisation(Organisation $organisation): array { return array_values(array_filter($this->memberships, static fn (OrganisationMembership $membership): bool => $membership->organisation()->id()->equals($organisation->id()) && $membership->isActive())); }
    public function findActiveByProfessionalAndOrganisation(Professional $professional, Organisation $organisation, ProfessionalRole $role): ?OrganisationMembership { foreach ($this->findActiveByProfessional($professional) as $membership) if ($membership->organisation()->id()->equals($organisation->id()) && $membership->role() === $role) return $membership; return null; }
}

final class InMemoryCredentialInvitations implements ProfessionalCredentialInvitationRepository
{
    /** @var list<ProfessionalCredentialInvitation> */ private array $invitations = [];
    public function save(ProfessionalCredentialInvitation $invitation): void { $this->invitations[] = $invitation; }
    public function findBySecret(string $secret): ?ProfessionalCredentialInvitation { foreach ($this->invitations as $invitation) if ($invitation->accepts($secret, new DateTimeImmutable('2026-08-14T12:00:00+00:00'))) return $invitation; return null; }
}

final class InMemoryUserPasswordHasher implements UserPasswordHasherInterface
{
    public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string { return 'hash:'.$plainPassword; }
    public function isPasswordValid(PasswordAuthenticatedUserInterface $user, string $plainPassword): bool { return $user->getPassword() === 'hash:'.$plainPassword; }
    public function needsRehash(PasswordAuthenticatedUserInterface $user): bool { return false; }
}

final class InMemoryProfessionalAccountAuditEvents implements ProfessionalAccountAuditEventRepository
{
    public function append(ProfessionalAccountAuditEvent $event): void {}
}
