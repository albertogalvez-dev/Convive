<?php

declare(strict_types=1);

namespace App\Tests\Professionals\DataFixtures;

use App\Organisations\DataFixtures\OrganisationFixtures;
use App\Professionals\DataFixtures\ProfessionalFixtures;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;

/**
 * Exercises the fixture classes directly against the transactional test
 * database (rolled back in tearDown), rather than running
 * `doctrine:fixtures:load`, which purges whatever database it targets
 * and must never run against development data without Alberto's
 * explicit approval.
 */
final class ProfessionalFixturesTest extends PostgreSqlTestCase
{
    public function testLoadingCreatesTwoProfessionalsWithDistinctRoles(): void
    {
        (new OrganisationFixtures())->load($this->entityManager);
        (new ProfessionalFixtures())->load($this->entityManager);

        $professionals = $this->entityManager
            ->getRepository(Professional::class)
            ->findAll();
        $memberships = $this->entityManager
            ->getRepository(OrganisationMembership::class)
            ->findAll();

        self::assertCount(2, $professionals);
        self::assertCount(2, $memberships);

        $roles = array_map(
            static fn (OrganisationMembership $membership): string =>
                $membership->role()->value,
            $memberships,
        );
        sort($roles);

        self::assertSame(['administrator', 'triage'], $roles);

        foreach ($memberships as $membership) {
            self::assertTrue($membership->isActive());
        }
    }
}
