<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TopProductsController extends AbstractController
{
    public function topProducts(
        ProductRepository $productRepository,
    ): Response {
        $topProducts = $productRepository->findTopMostOrdered(3);

        return $this->render('home/_top_products.html.twig', [
            'topProducts' => $topProducts,
        ]);
    }
}
