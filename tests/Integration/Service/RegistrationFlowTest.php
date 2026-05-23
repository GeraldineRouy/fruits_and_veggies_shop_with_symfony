<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationFlowTest extends WebTestCase
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
    public function fullRegistrationVerificationAndLoginFlow(): void
    {
        // 1. Register
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Créer mon compte')->form();
        $form['register[email]'] = 'flow@example.com';
        $form['register[firstName]'] = 'Flow';
        $form['register[lastName]'] = 'User';
        $form['register[plainPassword][first]'] = 'secret123';
        $form['register[plainPassword][second]'] = 'secret123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/register/check-email?email=flow@example.com');

        // 2. Verify user created in DB with token
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('flow@example.com');

        $this->assertNotNull($user);
        $this->assertNotNull($user->getEmailVerificationToken());
        $this->assertNull($user->getVerifiedAt());

        // 3. Verify email via token
        $this->client->request('GET', '/verify-email', ['token' => $user->getEmailVerificationToken()]);
        $this->assertResponseRedirects('/login');

        // 4. Check user is now verified
        $verifiedUser = $userRepository->findOneByEmail('flow@example.com');
        $this->assertNotNull($verifiedUser->getVerifiedAt());
        $this->assertNull($verifiedUser->getEmailVerificationToken());

        // 5. Login with valid credentials
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'flow@example.com';
        $form['_password'] = 'secret123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/');
    }

    #[Test]
    public function loginWithoutVerificationIsBlocked(): void
    {
        // 1. Register (don't verify)
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Créer mon compte')->form();
        $form['register[email]'] = 'noverify@example.com';
        $form['register[firstName]'] = 'No';
        $form['register[lastName]'] = 'Verify';
        $form['register[plainPassword][first]'] = 'secret123';
        $form['register[plainPassword][second]'] = 'secret123';
        $this->client->submit($form);

        $this->assertResponseRedirects();

        // 2. Try to login without verifying
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'noverify@example.com';
        $form['_password'] = 'secret123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
    }
}
