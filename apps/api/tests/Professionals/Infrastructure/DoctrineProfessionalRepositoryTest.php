<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Infrastructure;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Infrastructure\DoctrineProfessionalRepository;
use App\Tests\Shared\Infrastructure\Persistence\PostgreSqlTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Uid\Uuid;

final class DoctrineProfessionalRepositoryTest extends PostgreSqlTestCase
{
    private DoctrineProfessionalRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DoctrineProfessionalRepository(
            $this->entityManager,
        );
    }

    public function testItSavesAndFindsAProfessionalByEmail(): void
    {
        $email = ProfessionalEmail::fromString('alex.rivera@example.com');
        $professional = new Professional(
            Uuid::v7(),
            'Alex Rivera',
            $email,
            new DateTimeImmutable(),
        );

        $this->repository->save($professional);
        $this->entityManager->clear();

        $found = $this->repository->findByEmail($email);

        self::assertNotNull($found);
        self::assertNotSame($professional, $found);
        self::assertSame(
            $professional->id()->toRfc4122(),
            $found->id()->toRfc4122(),
        );
        self::assertTrue($email->equals($found->email()));
    }

    public function testItReturnsNullWhenNoProfessionalHasThatEmail(): void
    {
        $found = $this->repository->findByEmail(
            ProfessionalEmail::fromString('unknown@example.com'),
        );

        self::assertNull($found);
    }

    public function testItRejectsADuplicateEmail(): void
    {
        $email = ProfessionalEmail::fromString('alex.rivera@example.com');
        $this->repository->save(
            new Professional(Uuid::v7(), 'Alex Rivera', $email, new DateTimeImmutable()),
        );

        $this->expectException(DbalException::class);

        $this->repository->save(
            new Professional(Uuid::v7(), 'Another Name', $email, new DateTimeImmutable()),
        );
    }
}
