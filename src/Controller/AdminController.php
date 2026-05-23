<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use App\Service\PaginationService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/commandes', name: 'app_admin_orders', methods: ['GET'])]
    public function orders(
        OrderRepository   $orderRepository,
        Request           $request,
        PaginationService $paginationService,
        OrderService      $orderService,
    ): Response
    {
        $page = max(1, (int)$request->query->get('page', '1'));

        $qb = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->orderBy('o.orderedAt', 'DESC');

        $pagination = $paginationService->paginateQuery($qb, $page);

        $ordersData = array_map(fn($order) => [
            'order' => $order,
            'total' => $orderService->getOrderTotal($order),
        ], iterator_to_array($pagination['items']));

        return $this->render('admin/orders.html.twig', [
            'ordersData' => $ordersData,
            'pagination' => $pagination,
            'status_labels' => [
                'confirmed' => 'Confirmée',
                'preparing' => 'En préparation',
                'shipped' => 'Expédiée',
                'delivered' => 'Livrée',
                'cancelled' => 'Annulée',
            ],
        ]);
    }

    #[Route('/commande/{id}', name: 'app_admin_order_detail', methods: ['GET'])]
    public function orderDetail(Order $order, OrderService $orderService): Response
    {
        $total = $orderService->getOrderTotal($order);

        $statusLabels = [
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
        ];

        $currentStatus = $order->getStatus();
        $nextStatuses = $currentStatus !== null
            ? $orderService->getPossibleNextStatuses($currentStatus)
            : [];

        return $this->render('admin/order.html.twig', [
            'order' => $order,
            'total' => $total,
            'status_label' => $statusLabels[$currentStatus?->value ?? ''] ?? $currentStatus?->value ?? '',
            'next_statuses' => $nextStatuses,
        ]);
    }

    #[Route('/commande/{id}/statut', name: 'app_admin_order_status', methods: ['POST'])]
    public function updateStatus(
        Order        $order,
        Request      $request,
        OrderService $orderService,
    ): Response
    {
        $statusValue = $request->request->get('status');
        $newStatus = is_string($statusValue) ? OrderStatus::tryFrom($statusValue) : null;

        if ($newStatus === null) {
            $this->addFlash('error', 'Statut invalide.');

            return $this->redirectToRoute('app_admin_order_detail', ['id' => $order->getId()]);
        }

        try {
            $orderService->transitionStatus($order, $newStatus);
            $this->addFlash('success', 'Le statut de la commande a été mis à jour.');
        } catch (RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_order_detail', ['id' => $order->getId()]);
    }

    #[Route('/commande/{id}/annuler', name: 'app_admin_order_cancel', methods: ['POST'])]
    public function cancelOrder(Order $order, OrderService $orderService): Response
    {
        try {
            $orderService->cancelOrder($order, isAdmin: true);
            $this->addFlash('success', 'La commande a été annulée.');
        } catch (RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_order_detail', ['id' => $order->getId()]);
    }
}
