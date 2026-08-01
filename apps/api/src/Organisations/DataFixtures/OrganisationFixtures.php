<?php

declare(strict_types=1);

namespace App\Organisations\DataFixtures;

use App\Organisations\Domain\Organisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

final class OrganisationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $organisation = new Organisation(
            Uuid::fromString('0192a5c0-1111-7000-8000-000000000001'),
            'IES Valle Sereno',
        );

        $manager->persist($organisation);
        $manager->flush();
    }
}
