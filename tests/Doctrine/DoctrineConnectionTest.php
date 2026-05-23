<?php

namespace App\Tests\Doctrine;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineConnectionTest extends KernelTestCase
{
    #[Test]
    public function connectionIsSuccessful(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $connection->executeQuery('SELECT 1');
        $this->assertTrue($connection->isConnected());
    }

    #[Test]
    public function databasePlatformIsPostgreSQL(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $platform = $connection->getDatabasePlatform();
        $this->assertInstanceOf(PostgreSQLPlatform::class, $platform);
    }
}
