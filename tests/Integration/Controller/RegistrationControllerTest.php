<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->createQuery('DELETE FROM App\Entity\OrderLine o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Order o')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\CartItem ci')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Cart c')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\ResetPasswordRequest r')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    #[Test]
    public function registerPageLoadsSuccessfully(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Inscription');
    }

    #[Test]
    public function registerWithValidDataCreatesUserAndRedirects(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Créer mon compte')->form();
        $form['register[email]'] = 'new@example.com';
        $form['register[firstName]'] = 'New';
        $form['register[lastName]'] = 'User';
        $form['register[plainPassword][first]'] = 'password123';
        $form['register[plainPassword][second]'] = 'password123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/register/check-email?email=new@example.com');

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneByEmail('new@example.com');

        $this->assertNotNull($user);
        $this->assertNotNull($user->getEmailVerificationToken());
        $this->assertNull($user->getVerifiedAt());
    }

    #[Test]
    public function registerWithDuplicateEmailShowsError(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $existingUser = new User();
        $existingUser->setEmail('duplicate@example.com');
        $existingUser->setFirstName('Existing');
        $existingUser->setLastName('User');
        $existingUser->setPassword('some_hash');

        $entityManager->persist($existingUser);
        $entityManager->flush();

        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Créer mon compte')->form();
        $form['register[email]'] = 'duplicate@example.com';
        $form['register[firstName]'] = 'Another';
        $form['register[lastName]'] = 'User';
        $form['register[plainPassword][first]'] = 'password123';
        $form['register[plainPassword][second]'] = 'password123';
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cet email est déjà utilisé', $crawler->text());
    }

    #[Test]
    public function registerWithShortPasswordShowsValidationError(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $form = $crawler->selectButton('Créer mon compte')->form();
        $form['register[email]'] = 'shortpw@example.com';
        $form['register[firstName]'] = 'Short';
        $form['register[lastName]'] = 'Pw';
        $form['register[plainPassword][first]'] = 'short';
        $form['register[plainPassword][second]'] = 'short';
        $crawler = $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('8 caractères', $crawler->text());
    }

    #[Test]
    public function verifyEmailWithValidToken(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('verify@example.com');
        $user->setFirstName('Verify');
        $user->setLastName('User');
        $user->setPassword('some_hash');
        $user->setEmailVerificationToken('valid_verify_token');

        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->request('GET', '/verify-email', ['token' => 'valid_verify_token']);

        $this->assertResponseRedirects('/login');

        $userRepository = $container->get(UserRepository::class);
        $updatedUser = $userRepository->findOneByEmail('verify@example.com');

        $this->assertNotNull($updatedUser->getVerifiedAt());
        $this->assertNull($updatedUser->getEmailVerificationToken());
    }

    #[Test]
    public function verifyEmailWithInvalidTokenShowsError(): void
    {
        $this->client->request('GET', '/verify-email', ['token' => 'invalid_token_12345']);

        $this->assertResponseRedirects('/register');
    }

    #[Test]
    public function verifyEmailWithAlreadyVerifiedUserRedirectsToLogin(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('already@example.com');
        $user->setFirstName('Already');
        $user->setLastName('Verified');
        $user->setPassword('some_hash');
        $user->setEmailVerificationToken('already_used_token');
        $user->setVerifiedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->request('GET', '/verify-email', ['token' => 'already_used_token']);

        $this->assertResponseRedirects('/login');
    }
}
