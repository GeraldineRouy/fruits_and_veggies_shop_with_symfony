<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Form\CategoryType;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use App\Service\PaginationService;
use App\Service\UserService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
    public function users(
        Request $request,
        UserRepository $userRepository,
        PaginationService $paginationService,
    ): Response {
        $page = max(1, (int) $request->query->get('page', '1'));

        $qb = $userRepository->createPaginatedQueryBuilder();
        $pagination = $paginationService->paginateQuery($qb, $page, 20);

        return $this->render('admin/users.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/utilisateur/{id}/toggle', name: 'app_admin_user_toggle', methods: ['POST'])]
    public function toggleUser(User $user, UserService $userService): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre statut.');

            return $this->redirectToRoute('app_admin_users');
        }

        $userService->deactivateUser($user);

        $status = $user->isActive() ? 'réactivé' : 'désactivé';
        $this->addFlash('success', "L'utilisateur " . $user->getEmail() . " a été " . $status . ".");

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/categories', name: 'app_admin_categories', methods: ['GET'])]
    public function categories(
        Request $request,
        CategoryRepository $categoryRepository,
        PaginationService $paginationService,
    ): Response {
        $page = max(1, (int) $request->query->get('page', '1'));

        $qb = $categoryRepository->createPaginatedQueryBuilder();
        $pagination = $paginationService->paginateQuery($qb, $page, 20);

        return $this->render('admin/categories.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/categories/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie "' . $category->getName() . '" a été créée.');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/category_form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/categories/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])]
    public function editCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie "' . $category->getName() . '" a été modifiée.');

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/category_form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/categories/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function deleteCategory(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-category-' . $category->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_categories');
        }

        try {
            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie "' . $category->getName() . '" a été supprimée.');
        } catch (RuntimeException $e) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie.');
        }

        return $this->redirectToRoute('app_admin_categories');
    }

    #[Route('/produits', name: 'app_admin_products', methods: ['GET'])]
    public function products(
        Request $request,
        ProductRepository $productRepository,
        PaginationService $paginationService,
    ): Response {
        $page = max(1, (int) $request->query->get('page', '1'));

        $qb = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->orderBy('p.name', 'ASC');

        $pagination = $paginationService->paginateQuery($qb, $page, 12);

        return $this->render('admin/products.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/produits/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function newProduct(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit "' . $product->getName() . '" a été créé.');

            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/product_form.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/produits/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le produit "' . $product->getName() . '" a été modifié.');

            return $this->redirectToRoute('app_admin_products');
        }

        return $this->render('admin/product_form.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/produits/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-product-' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_products');
        }

        try {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit "' . $product->getName() . '" a été supprimé.');
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Ce produit ne peut pas être supprimé car il est référencé dans des commandes.');
        } catch (RuntimeException $e) {
            $this->addFlash('error', 'Impossible de supprimer ce produit.');
        }

        return $this->redirectToRoute('app_admin_products');
    }
}
