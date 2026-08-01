<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PostgreSqlTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->entityManager = self::getContainer()->get(
            EntityManagerInterface::class,
        );

        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();

            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $this->entityManager->clear();
        }

        parent::tearDown();
    }
}
