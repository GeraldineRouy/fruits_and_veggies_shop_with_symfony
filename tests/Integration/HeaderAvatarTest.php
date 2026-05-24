<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HeaderAvatarTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Product p')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->entityManager = $entityManager;
    }

    #[Test]
    public function loggedInUserSeesUserAvatar(): void
    {
        $user = $this->createUser('user@example.com', ['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('assets/images/avatars/avatar_user.png', $content);
    }

    #[Test]
    public function loggedInAdminSeesAdminAvatar(): void
    {
        $user = $this->createUser('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('assets/images/avatars/avatar_admin.png', $content);
    }

    #[Test]
    public function visitorDoesNotSeeAnyAvatar(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('assets/images/avatars/', $content);
    }

    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
