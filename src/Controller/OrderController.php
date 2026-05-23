<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\OrderService;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/profile/commandes', name: 'app_order_list', methods: ['GET'])]
    public function list(
        OrderRepository $orderRepository,
        OrderService    $orderService,
    ): Response
    {
        $orders = $orderRepository->findBy(
            ['user' => $this->getUser()],
            ['orderedAt' => 'DESC'],
        );

        $ordersData = array_map(fn(Order $o) => [
            'order' => $o,
            'total' => $orderService->getOrderTotal($o),
        ], $orders);

        return $this->render('profile/orders.html.twig', [
            'ordersData' => $ordersData,
            'status_labels' => [
                'confirmed' => 'Confirmée',
                'preparing' => 'En préparation',
                'shipped' => 'Expédiée',
                'delivered' => 'Livrée',
                'cancelled' => 'Annulée',
            ],
        ]);
    }

    #[Route('/profile/commande/{id}', name: 'app_order_detail', methods: ['GET'])]
    public function detail(Order $order, OrderService $orderService): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        $total = $orderService->getOrderTotal($order);

        $statusLabels = [
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
        ];

        return $this->render('profile/order.html.twig', [
            'order' => $order,
            'total' => $total,
            'status_label' => $statusLabels[$order->getStatus()?->value ?? ''] ?? $order->getStatus()?->value ?? '',
        ]);
    }

    #[Route('/profile/commande/{id}/annuler', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(Order $order, OrderService $orderService): Response
    {
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        try {
            $orderService->cancelOrder($order, isAdmin: false);
            $this->addFlash('success', 'Votre commande a été annulée.');
        } catch (RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_detail', ['id' => $order->getId()]);
    }

    #[Route('/commande/valider', name: 'app_order_checkout', methods: ['POST'])]
    public function checkout(CartService $cartService): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $order = $cartService->cartToOrder($user);
            $this->addFlash('success', 'Votre commande a été créée avec succès.');

            return $this->redirectToRoute('app_order_detail', ['id' => $order->getId()]);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_cart_index');
        }
    }
}
