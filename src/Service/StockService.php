<?php

        namespace App\Service;

        use App\Entity\Product;
        use App\Repository\StockMovementRepository;
        use Doctrine\ORM\EntityManagerInterface;
        use App\Entity\StockMovement;
        use App\Entity\User;
        use App\Entity\OrderLine;
        use App\Entity\Order;
        use App\Enum\StockMovementType;
        use DateTimeImmutable;
use Symfony\Component\HttpKernel\HttpCache\Store;

        class StockService
        {
            private StockMovementRepository $stockMovementRepository;
            private EntityManagerInterface $entityManager;

            public function __construct(
                StockMovementRepository $stockMovementRepository,
                EntityManagerInterface $entityManager
            )
            {
                $this->stockMovementRepository = $stockMovementRepository;
                $this->entityManager = $entityManager;
            }

            public function getCurrentStock(Product $product): int
            {
                return $this->stockMovementRepository->calculateStockForProduct($product);
            }


            /**
             * Crée un mouvement de stock manuel (ajustement, stock initial, etc.).
             *
             * @param Product $product Le produit concerné.
             * @param int $quantity La quantité à ajouter ou retirer.
             * @param StockMovementType $type Le type de mouvement (IN, OUT, ADJUSTMENT).
             * @param string $reason La raison du mouvement.
             * @param User|null $user L'utilisateur qui a effectué l'ajustement.
             */
            public function createAdjustmentMovement(
                Product $product,
                int $quantity,
                StockMovementType $type,
                string $reason,
                ?User $user = null
                ): StockMovement
            {
                if ($quantity<=0){
                    throw new \InvalidArgumentException("La quantité doit être un entier positif.");
                }

                $movement = new StockMovement();
                $movement->setProduct($product);
                $movement->setQuantity($quantity);
                $movement->setType($type);
                $movement->setReason($reason);
                $movement->setDate(new \DateTimeImmutable());

                if($user !== null){
                    $movement->setUserId($user);
                };

                $this->entityManager->persist($movement);
                $this->entityManager->flush();

                return $movement;
            }

            /**
             * Crée un mouvement de stock automatique (IN ou OUT) basé sur une ligne de commande.
             *
             * @param OrderLine $orderLine La ligne de commande à traiter.
             * @param StockMovementType $type Le type de mouvement (IN pour Achat/Réception, OUT pour Vente/Expédition).
             * @param User|null $user L'utilisateur (facultatif, ici on pourrait utiliser l'utilisateur de l'Order).
             * @return StockMovement
             * @throws \InvalidArgumentException si la quantité est invalide.
             */
            public function createOrderMovement(
                OrderLine $orderLine,
                StockMovementType $type,
                ?User $user = null
            ): StockMovement
            {
                $quantity = $orderLine->getQuantity();
                $product = $orderLine->getProductId();
                $order = $orderLine->getOrderId();

                if( $quantity<=0){
                    throw new \InvalidArgumentException("La quantité doit être un entier positif.");
                }
                if (!$product){
                    throw new \InvalidArgumentException("Le produit associé à la ligne de commande est invalide.");
                }
                
                $order_id = $order ? $order->getId() : 'N/A';

                $reason = sprintf(
                    "Mouvement de stock pour la ligne de commande ID %s (Type de commande: %s)",
                    $type->name,
                    $order_id,
                    $orderLine->getId() ?? 0
                );

                $movement = new StockMovement();
                $movement->setProduct($product);
                $movement->setQuantity($quantity);
                $movement->setType($type);
                $movement->setReason($reason);
                $movement->setDate(new \DateTimeImmutable());

                if($user !== null){
                    $movement->setUserId($user);
                };

                $this->entityManager->persist($movement);

                // Nous ne faisons pas de flush ici car le OrderController va tout flusher en bloc.
                // C'est souvent mieux de gérer les transactions au niveau du Controller/Service appelant.
                // Pour l'instant, laissons le flush pour le MVP simple, mais gardez cette note en tête.
                $this->entityManager->flush();

                return $movement;
            }


        }