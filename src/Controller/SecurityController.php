<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Form\DTO\RegisterDto;
use App\Form\ForgotPasswordType;
use App\Form\RegisterType;
use App\Form\ResetPasswordType;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use App\Service\UserService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(Request $request, UserService $userService, MailerService $mailerService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $dto = new RegisterDto();
        $form = $this->createForm(RegisterType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user->setEmail($dto->getEmail());
            $user->setFirstName($dto->getFirstName());
            $user->setLastName($dto->getLastName());

            try {
                $userService->register($user, $dto->getPlainPassword());
            } catch (RuntimeException) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');

                return $this->render('security/register.html.twig', [
                    'form' => $form,
                ]);
            }

            $mailerService->sendValidationEmail($user);

            return $this->redirectToRoute('app_register_check_email', [
                'email' => $user->getEmail(),
            ]);
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/register/check-email', name: 'app_register_check_email')]
    public function checkEmail(Request $request): Response
    {
        return $this->render('security/check_email.html.twig', [
            'email' => $request->query->get('email'),
        ]);
    }

    #[Route(path: '/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, UserService $userService): Response
    {
        $token = $request->query->get('token');

        if ($token === null) {
            $this->addFlash('error', 'Lien de validation invalide.');

            return $this->redirectToRoute('app_register');
        }

        try {
            $userService->validateEmail($token);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'Email already verified') {
                $this->addFlash('info', 'Votre email est déjà vérifié.');

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'Lien de validation invalide.');

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Votre email a été vérifié avec succès. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, MailerService $mailerService, UserService $userService, UserRepository $userRepository): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneByEmail($email);

            if ($user !== null) {
                $token = $userService->requestPasswordReset($user);
                $mailerService->sendPasswordResetEmail($user, $token);
            }

            $this->addFlash('info', 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/reset-password', name: 'app_reset_password')]
    public function resetPassword(Request $request, UserService $userService, ResetPasswordRequestRepository $resetPasswordRequestRepository): Response
    {
        $token = $request->query->get('token');

        if ($token === null) {
            $this->addFlash('error', 'Lien de réinitialisation invalide.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $resetRequest = $resetPasswordRequestRepository->findOneByToken($token);

        if ($resetRequest === null) {
            $this->addFlash('error', 'Lien de réinitialisation invalide.');

            return $this->redirectToRoute('app_forgot_password');
        }

        if ($resetRequest->isExpired()) {
            $this->addFlash('error', 'Ce lien a expiré. Veuillez refaire une demande.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            try {
                $userService->resetPassword($token, $plainPassword);
            } catch (RuntimeException) {
                $this->addFlash('error', 'Lien de réinitialisation invalide.');

                return $this->redirectToRoute('app_forgot_password');
            }

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form,
            'token' => $token,
        ]);
    }
}
