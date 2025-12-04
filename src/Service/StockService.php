<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\StockMovementRepository;

class StockService
{
    private StockMovementRepository $stockMovementRepository;

    public function __construct(StockMovementRepository $stockMovementRepository)
    {
        $this->stockMovementRepository = $stockMovementRepository;
    }

    public function getCurrentStock(Product $product): int
    {
        return $this->stockMovementRepository->calculateStockForProduct($product);
    }
}