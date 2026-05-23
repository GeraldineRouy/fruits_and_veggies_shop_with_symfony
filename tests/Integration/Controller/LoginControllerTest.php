<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function loginPageLoadsSuccessfully(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');
        $this->assertSelectorExists('form');
    }

    #[Test]
    public function loginWithValidCredentialsSucceeds(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $user = new User();
        $user->setEmail('valid@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
        $user->setVerifiedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'valid@example.com';
        $form['_password'] = 'password123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/');
    }

    #[Test]
    public function loginWithInvalidCredentialsShowsError(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'unknown@example.com';
        $form['_password'] = 'wrong_password';
        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function loginWithoutEmailVerificationIsBlocked(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $user = new User();
        $user->setEmail('unverified@example.com');
        $user->setFirstName('Unverified');
        $user->setLastName('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'unverified@example.com';
        $form['_password'] = 'password123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function alreadyLoggedInUserIsRedirectedFromLogin(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $user = new User();
        $user->setEmail('loggedin@example.com');
        $user->setFirstName('Logged');
        $user->setLastName('In');
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
        $user->setVerifiedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->loginUser($user);

        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/');
    }
}
