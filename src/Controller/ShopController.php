<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    #[Route('/boutique/{id}', name: 'app_shop_category', requirements: ['id' => '\d+'])]
    public function category(
        Category $category,
        Request $request,
        ProductRepository $productRepository,
        PaginationService $paginationService,
    ): Response {
        $page = $request->query->getInt('page', 1);
        $paginator = $productRepository->findByCategoryPaginated($category, $page);
        $pagination = $paginationService->paginate($paginator, $page);

        return $this->render('shop/products.html.twig', [
            'category' => $category,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/boutique/produit/{id}', name: 'app_shop_product', requirements: ['id' => '\d+'])]
    public function product(Product $product): Response
    {
        return $this->render('shop/product.html.twig', [
            'product' => $product,
        ]);
    }
}
