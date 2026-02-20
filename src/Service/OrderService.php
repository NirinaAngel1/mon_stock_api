<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\StockMovementType;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\StockService;
use App\Enum\OrderStatus;
use App\Enum\OrderType;

class OrderService
{

    private StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    
    }

    public function resolveStockMovementType(Order $order):StockMovementType
    {
        return match($order->getType()){
            OrderType::SALES => StockMovementType::OUT,
            OrderType::PURCHASE => StockMovementType::IN,
        };
    }

    public function validateOrder(
        Order $order,
        User $user,
        EntityManagerInterface $entityManager):void
    {
        $entityManager->getConnection()->beginTransaction();

        $movementType = $this->resolveStockMovementType($order);

        try{
            if ($order->getStatus()!==OrderStatus::PENDING){
                throw new \LogicException("Impossible de valider une commande non PENDING");
            }

            foreach($order->getOrderLines() as $lines ){
                $product = $lines->getProductId();
                $stock = $this->stockService->getCurrentStock($product);

                if($movementType === StockMovementType::OUT && $stock < $lines->getQuantity()){
                    throw new \LogicException(
                    sprintf('Stock insuffisant pour le produit %s', $lines->getProductId()->getName())
                );
                }

                $this->stockService->createOrderMovement($lines, $movementType, $user);
            }

            $order->setStatus(OrderStatus::COMPLETED);

            $entityManager->persist($order);
            $entityManager->flush();
            $entityManager->getConnection()->commit();

        }catch(\Throwable $e){
            $entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

}