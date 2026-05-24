<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 15)]
class UserDeactivatedSubscriber
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        if ($user->isActive()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->hasSession()) {
            $request->getSession()->invalidate();
            $request->getSession()->getFlashBag()->add(
                'danger',
                'Votre compte a été désactivé. Contactez l\'administrateur.',
            );
        }

        $this->tokenStorage->setToken(null);

        $loginUrl = $this->router->generate('app_login');
        $event->setResponse(new RedirectResponse($loginUrl));
    }
}
