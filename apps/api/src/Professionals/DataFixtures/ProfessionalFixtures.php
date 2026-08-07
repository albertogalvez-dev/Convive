<?php

declare(strict_types=1);

namespace App\Professionals\DataFixtures;

use App\Organisations\DataFixtures\OrganisationFixtures;
use App\Organisations\Domain\Organisation;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class ProfessionalFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organisation = $manager->getRepository(Organisation::class)->findOneBy(
            ['name' => 'IES Valle Sereno'],
        );

        $triageProfessional = new Professional(
            Uuid::fromString('0192a5c0-3333-7000-8000-000000000001'),
            'Alex Rivera',
            ProfessionalEmail::fromString('alex.rivera@ies-valle-sereno.example'),
            new DateTimeImmutable(),
        );
        $administratorProfessional = new Professional(
            Uuid::fromString('0192a5c0-3333-7000-8000-000000000002'),
            'Sam Okafor',
            ProfessionalEmail::fromString('sam.okafor@ies-valle-sereno.example'),
            new DateTimeImmutable(),
        );

        $manager->persist($triageProfessional);
        $manager->persist($administratorProfessional);

        $manager->persist(new OrganisationMembership(
            Uuid::v7(),
            $triageProfessional,
            $organisation,
            ProfessionalRole::Triage,
            new DateTimeImmutable(),
        ));
        $manager->persist(new OrganisationMembership(
            Uuid::v7(),
            $administratorProfessional,
            $organisation,
            ProfessionalRole::Administrator,
            new DateTimeImmutable(),
        ));

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OrganisationFixtures::class];
    }
}
