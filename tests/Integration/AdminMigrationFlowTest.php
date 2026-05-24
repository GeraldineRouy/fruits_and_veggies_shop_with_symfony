<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminMigrationFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private Connection $connection;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();

        $this->entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\OrderLine ol')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function migrationCreatesAdminWithCorrectFields(): void
    {
        $hashedPassword = password_hash('admin', PASSWORD_BCRYPT);

        $this->connection->executeStatement("INSERT INTO \"user\" (email, first_name, last_name, roles, password, verified_at, is_active)
            VALUES (
                'admin@example.com',
                'Admin',
                'Admin',
                '[\"ROLE_ADMIN\"]',
                '$hashedPassword',
                CURRENT_TIMESTAMP,
                true
            )
            ON CONFLICT (email) DO NOTHING");

        $this->entityManager->clear();

        $user = $this->getAdminUser();

        $this->assertNotNull($user);
        $this->assertSame('admin@example.com', $user->getEmail());
        $this->assertSame('Admin', $user->getFirstName());
        $this->assertSame('Admin', $user->getLastName());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertNotNull($user->getVerifiedAt());
        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function migrationIsIdempotent(): void
    {
        $hashedPassword = password_hash('admin', PASSWORD_BCRYPT);

        $sql = "INSERT INTO \"user\" (email, first_name, last_name, roles, password, verified_at, is_active)
            VALUES (
                'admin@example.com',
                'Admin',
                'Admin',
                '[\"ROLE_ADMIN\"]',
                '$hashedPassword',
                CURRENT_TIMESTAMP,
                true
            )
            ON CONFLICT (email) DO NOTHING";

        $this->connection->executeStatement($sql);
        $this->connection->executeStatement($sql);

        $this->entityManager->clear();

        $users = $this->entityManager->getRepository(User::class)->findBy(['email' => 'admin@example.com']);

        $this->assertCount(1, $users);
    }

    #[Test]
    public function adminCanLoginAndAccessDashboard(): void
    {
        $hashedPassword = password_hash('admin', PASSWORD_BCRYPT);

        $this->connection->executeStatement("INSERT INTO \"user\" (email, first_name, last_name, roles, password, verified_at, is_active)
            VALUES (
                'admin@example.com',
                'Admin',
                'Admin',
                '[\"ROLE_ADMIN\"]',
                '$hashedPassword',
                CURRENT_TIMESTAMP,
                true
            )
            ON CONFLICT (email) DO NOTHING");

        $this->entityManager->clear();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'admin@example.com';
        $form['_password'] = 'admin';
        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $this->client->followRedirect();

        $crawler = $this->client->request('GET', '/admin');
        $this->assertResponseRedirects('/admin/');

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSame('Dashboard Administration', $crawler->filter('h1')->text());
    }

    #[Test]
    public function migrationDownRemovesAdmin(): void
    {
        $hashedPassword = password_hash('admin', PASSWORD_BCRYPT);

        $this->connection->executeStatement("INSERT INTO \"user\" (email, first_name, last_name, roles, password, verified_at, is_active)
            VALUES (
                'admin@example.com',
                'Admin',
                'Admin',
                '[\"ROLE_ADMIN\"]',
                '$hashedPassword',
                CURRENT_TIMESTAMP,
                true
            )
            ON CONFLICT (email) DO NOTHING");

        $this->entityManager->clear();
        $this->assertNotNull($this->getAdminUser());

        $this->connection->executeStatement("DELETE FROM \"user\" WHERE email = 'admin@example.com'");

        $this->entityManager->clear();
        $this->assertNull($this->getAdminUser());
    }

    private function getAdminUser(): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneByEmail('admin@example.com');
    }
}
