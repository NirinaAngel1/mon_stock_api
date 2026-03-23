<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ProductRepository;
use App\Repository\StockMovementRepository;
use App\Service\StockService;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/dashboard', name:'api_dashboard_')]
final class DashboardController extends AbstractController
{
    public function __construct(private readonly ProductRepository $productRepository,
        private readonly StockService $stockService)
    {}

    #[Route("", name:"index", methods:["GET"])]
     public function dashboard(StockMovementRepository $stockMovementRepository): JsonResponse
    {
        $totalProducts = $this->productRepository->count([]);
        $outOfStock = $this->productRepository->findOutOfStock();
        $lowStock = $this->productRepository->findLowStock();
        $lastMovements = $this->stockService->getLastMovementGlobal(5);

        return $this->json([
            'summary'=>[
            'totalProducts' => $totalProducts,
            'outOfStock' => count($outOfStock),
            'lowStock' => count($lowStock),
            ],
            'lastMovements' => $lastMovements
        ]);
    }

    #[Route("/criticals-products", name:"critical_products", methods:["GET"])]
    public function criticalProducts(): JsonResponse
    {
        $products = $this->productRepository->findLowStock();
        return $this->json($products);
         
    }

    #[Route("/stats/monthly", name:"monthly_stats", methods:["GET"])]
    public function monthlyStats(StockMovementRepository $stockMovementRepository): JsonResponse
    {
        $stats = $stockMovementRepository->getMonthlyMovements();

        return $this->json($stats);
    }

    #[Route('/stock',name: 'stock', methods:['GET'])]
    public function index(
        StockMovementRepository $stockMovement,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $products = $this->productRepository->findAll();


        $data = [];

        foreach($products as $product){
            $currentStock = $this->stockService->getCurrentStock($product);

            $lowstock = $currentStock <= $product->getLowStockThreshold();

            $lastMovements = $this->stockService->getLastMovement($product, 5);

            $data[]=[
                'product'=>[
                    'id'=>$product->getId(),
                    'name'=>$product->getName()
                ],
                'currentStock' => $currentStock,
                'lowStock' => $lowstock,
                'lastMovements' => $lastMovements
            ];
        }

        return $this->json($data);
    }

    #[Route('/summary', name:'summary', methods:['GET'])]
    public function summary():JsonResponse
    {
        $totalProducts = $this->productRepository->count([]);

        //variable à utiliser pour la methode countoufstock dans repository
        // $outOfStock = $this->productRepository->CountOutOfStock();

        $outOfStock = $this->productRepository->findOutOfStock();
        $lowStock = $this->productRepository->findLowStock();

        return $this->json([
            'total_products' => $totalProducts,
            'out_of_stock' => count($outOfStock),
            'low_stock' => count($lowStock)
        ]);
    }

    #[Route('/last-movements', name:'last_movements', methods:['GET'])]
    public function getLastMovement():JsonResponse
    {
        $limit = 5;

        $data = $this->stockService->getLastMovementGlobal($limit);
        
        return $this->json($data);
    }
}
