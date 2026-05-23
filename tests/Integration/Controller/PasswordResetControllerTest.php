<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PasswordResetControllerTest extends WebTestCase
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
    public function forgotPasswordPageLoadsSuccessfully(): void
    {
        $crawler = $this->client->request('GET', '/forgot-password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mot de passe oublié');
    }

    #[Test]
    public function forgotPasswordWithExistingEmailShowsSameMessage(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('reset@example.com');
        $user->setFirstName('Reset');
        $user->setLastName('User');
        $user->setPassword('some_hash');

        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $this->client->request('GET', '/forgot-password');
        $form = $crawler->selectButton('Envoyer')->form();
        $form['forgot_password[email]'] = 'reset@example.com';
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Si un compte existe avec cet email', $crawler->text());
    }

    #[Test]
    public function forgotPasswordWithUnknownEmailShowsSameMessage(): void
    {
        $crawler = $this->client->request('GET', '/forgot-password');
        $form = $crawler->selectButton('Envoyer')->form();
        $form['forgot_password[email]'] = 'unknown@example.com';
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Si un compte existe avec cet email', $crawler->text());
    }

    #[Test]
    public function resetPasswordWithValidTokenShowsForm(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('resetter@example.com');
        $user->setFirstName('Resetter');
        $user->setLastName('User');
        $user->setPassword('old_hash');

        $entityManager->persist($user);
        $entityManager->flush();

        $userService = $container->get('App\Service\UserService');
        $token = $userService->requestPasswordReset($user);

        $crawler = $this->client->request('GET', '/reset-password', ['token' => $token]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Réinitialiser mon mot de passe');
    }

    #[Test]
    public function resetPasswordWithValidTokenAndNewPasswordSucceeds(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get('security.password_hasher');

        $user = new User();
        $user->setEmail('changer@example.com');
        $user->setFirstName('Changer');
        $user->setLastName('User');
        $user->setPassword($passwordHasher->hashPassword($user, 'old_password'));

        $entityManager->persist($user);
        $entityManager->flush();

        $userService = $container->get('App\Service\UserService');
        $token = $userService->requestPasswordReset($user);

        $crawler = $this->client->request('GET', '/reset-password', ['token' => $token]);
        $form = $crawler->selectButton('Réinitialiser mon mot de passe')->form();
        $form['reset_password[plainPassword][first]'] = 'new_password123';
        $form['reset_password[plainPassword][second]'] = 'new_password123';
        $this->client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    #[Test]
    public function resetPasswordWithInvalidTokenRedirects(): void
    {
        $this->client->request('GET', '/reset-password', ['token' => 'invalid_token_xyz']);

        $this->assertResponseRedirects('/forgot-password');
    }

    #[Test]
    public function resetPasswordWithoutTokenRedirects(): void
    {
        $this->client->request('GET', '/reset-password');

        $this->assertResponseRedirects('/forgot-password');
    }
}
