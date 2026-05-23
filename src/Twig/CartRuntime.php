<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

class CartRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Security $security,
    ) {}

    public function getCartItemCount(): int
    {
        $user = $this->security->getUser();

        if ($user === null) {
            return 0;
        }

        try {
            return $this->cartService->getProductCount($user);
        } catch (\RuntimeException) {
            return 0;
        }
    }
}
