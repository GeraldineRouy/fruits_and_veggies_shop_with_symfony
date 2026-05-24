<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Form\Cart\AddToCartType;
use App\Form\Cart\UpdateCartItemType;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        $user = $this->getUser();
        $items = $cartService->getItems($user);
        $total = $cartService->getTotal($user);
        $productCount = $cartService->getProductCount($user);

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'total' => $total,
            'productCount' => $productCount,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        Product $product,
        Request $request,
        CartService $cartService,
    ): Response {
        $form = $this->createForm(AddToCartType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = (int) $form->get('quantity')->getData();
            $cartService->addProduct($this->getUser(), $product, $quantity);
            $this->addFlash('success', 'Le produit a été ajouté au panier.');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function updateQuantity(
        CartItem $cartItem,
        Request $request,
        CartService $cartService,
    ): Response {
        if ($cartItem->getCart()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cet article ne vous appartient pas.');
        }

        $form = $this->createForm(UpdateCartItemType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quantity = (int) $form->get('quantity')->getData();
            $cartService->updateItemQuantity($cartItem, $quantity);
            $this->addFlash('success', 'La quantité a été modifiée.');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        CartItem $cartItem,
        CartService $cartService,
    ): Response {
        if ($cartItem->getCart()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cet article ne vous appartient pas.');
        }

        $cartService->removeItem($cartItem);
        $this->addFlash('success', 'Le produit a été retiré du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(CartService $cartService): Response
    {
        $cartService->clearCart($this->getUser());
        $this->addFlash('success', 'Le panier a été vidé.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/preview', name: 'app_cart_preview', methods: ['GET'])]
    public function preview(CartService $cartService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $cart = $cartService->getOrCreateCart($user);
        $items = $cart->getItems();

        return $this->render('cart/_preview.html.twig', [
            'items' => $items,
            'total' => $cartService->getTotal($user),
            'count' => $cartService->getProductCount($user),
        ]);
    }
}
