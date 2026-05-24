<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminControllerTest extends WebTestCase
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
    public function adminCanViewUserList(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword($passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setVerifiedAt(new \DateTimeImmutable());

        $entityManager->persist($admin);
        $entityManager->flush();

        $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/utilisateurs');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des utilisateurs');
    }

    #[Test]
    public function adminCanDeactivateUser(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword($passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setVerifiedAt(new \DateTimeImmutable());
        $entityManager->persist($admin);

        $target = new User();
        $target->setEmail('target@test.com');
        $target->setFirstName('Target');
        $target->setLastName('User');
        $target->setPassword($passwordHasher->hashPassword($target, 'password'));
        $target->setIsActive(true);
        $entityManager->persist($target);
        $entityManager->flush();

        $this->client->loginUser($admin);

        $this->client->request('POST', '/admin/utilisateur/' . $target->getId() . '/toggle', [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('toggle-user-' . $target->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/utilisateurs');

        $entityManager->clear();
        $deactivatedUser = $entityManager->find(User::class, $target->getId());
        $this->assertFalse($deactivatedUser->isActive());
    }

    #[Test]
    public function adminCannotDeactivateSelf(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setPassword($passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setVerifiedAt(new \DateTimeImmutable());
        $entityManager->persist($admin);
        $entityManager->flush();

        $this->client->loginUser($admin);

        $this->client->request('POST', '/admin/utilisateur/' . $admin->getId() . '/toggle', [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('toggle-user-' . $admin->getId())->getValue(),
        ]);

        $this->assertResponseRedirects('/admin/utilisateurs');

        $entityManager->clear();
        $reloadedAdmin = $entityManager->find(User::class, $admin->getId());
        $this->assertTrue($reloadedAdmin->isActive());
    }
}
