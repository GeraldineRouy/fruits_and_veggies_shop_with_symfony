<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserDeactivatedSubscriberTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function deactivatedUserIsLoggedOutOnNextRequest(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $user = new User();
        $user->setEmail('user@test.com');
        $user->setFirstName('Normal');
        $user->setLastName('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $user->setVerifiedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/admin/commandes');
        $this->assertResponseStatusCodeSame(403);

        $user->setIsActive(false);
        $entityManager->flush();

        $entityManager->clear();

        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }
}
