<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use App\Service\StockService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/stock')]
final class StockController extends AbstractController
{
    public function __construct(private StockService $stockService)
    {}

    #[Route('/history', name:'app_stock_history_all', methods:['GET'])]
    public function getAllStockHistory(
        StockMovementRepository $stockMovementRepository,
        Request $request,
    ):JsonResponse
    {
        $limit = (int) $request->query->get('limit',20);

        $movement = $stockMovementRepository->findLastMovementGlobal($limit);

        return $this->json($movement, Response::HTTP_OK, [], ['groups'=>'stock:read']);
    }

    #[Route('/{id}', name: 'app_product_stock', methods:['GET'])]
    public function getStock(
        Product $product
        ): JsonResponse
    {
        if(!$product){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Produit introuvable ou inexistant'
            ]);
        }

        $stock = $this->stockService->getCurrentStock($product);
        return new JsonResponse([
            'product_id'=>$product->getId(),
            'product_name'=>$product->getName(),
            'stock'=>$stock
        ]);
    }

    // historique des stocks par produit (id prod)
    #[Route('/{id}/stock-history', requirements:['id'=>'\d+'], name:'app_stock_history_id', methods:['GET'])]
    public function getStockHistory(
        int $id,
        StockMovementRepository $repo,
        ProductRepository $productRepository,
        ):JsonResponse
    {
        $product = $productRepository->find($id);

        if(!$product){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Produit introuvable ou inexistant'
            ]);
        }

        $movements = $repo->findByProduct($product, 20);
        
        return $this->json($movements, Response::HTTP_OK,[], ['groups'=>['read:product:item']]);
    }
}
