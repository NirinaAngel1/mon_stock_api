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
        use InvalidArgumentException;

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

            //fonction pour calculer les stocks 
            public function getCurrentStock(Product $product): int
            {
                $qb = $this->entityManager->createQueryBuilder();

                $strExpr = $this->stockMovementRepository->getStockExpression();

                $qb->select("$strExpr as stock")
                    ->from(StockMovement::class, 'sm')
                    ->where('sm.product = :product')
                    ->setParameter('product', $product)
                    ->setParameter('inType', StockMovementType::IN)
                    ->setParameter('outType', StockMovementType::OUT)
                    ->setParameter('adjType', StockMovementType::ADJUSTMENT);
                
                    $result = $qb->getQuery()->getSingleResult();

                    return (int)$result['stock'] ?? 0;
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
                $currentStock = $this->getCurrentStock($product);

                if($type === StockMovementType::OUT && $quantity > $currentStock){
                    throw new \DomainException("Vérifier la quantité, stock insuffisant pour effectuer la sortie");
                }

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
                    $movement->setUser($user);
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
                $product = $orderLine->getProduct();
                $order = $orderLine->getOrder();

                $currentStock = $this->getCurrentStock($product);
                
                if($type === StockMovementType::OUT && $quantity > $currentStock){
                        throw new \DomainException("Vérifier la quantité, stock insuffisant pour effectuer la sortie");
                }

                if($type === StockMovementType::ADJUSTMENT && $quantity < 0){
                    if(abs($quantity)>$currentStock){
                        throw new \DomainException("Ajustement impossible : risque de stock négatif");
                    }
                }

                if($quantity<=0){
                    throw new InvalidArgumentException("La quantité doit être positive");
                }

                if (!$product){
                    throw new \InvalidArgumentException("Le produit associé à la ligne de commande est invalide.");
                }
                
                $order_id = $order ? $order->getId() : 'N/A';

                $reason = sprintf(
                    "Commande #%s - ligne #%s (%s)",
                    $order?->getReference() ?? $order_id,
                    $orderLine->getId(),
                    $type->value
                );

                $movement = new StockMovement();
                $movement->setProduct($product);
                $movement->setQuantity($quantity);
                $movement->setType($type);
                $movement->setReason($reason);
                $movement->setDate(new \DateTimeImmutable());
                $movement->setOrderLine($orderLine);

                if($user !== null){
                    $movement->setUser($user);
                };

                $this->entityManager->persist($movement);

                // Nous ne faisons pas de flush ici car le OrderController va tout flusher en bloc.
                // C'est souvent mieux de gérer les transactions au niveau du Controller/Service appelant.
                // Pour l'instant, laissons le flush pour le MVP simple, mais gardez cette note en tête.
                $this->entityManager->flush();

                return $movement;
            }

            public function getLastMovementGlobal(int $limit):array
            {
                $movements = $this->stockMovementRepository->findLastMovementGlobal($limit);
                
                return array_map(fn($m) =>[
                    'id' => $m->getId(),
                    'name' => $m->getProduct()->getName(),
                    'type' => $m->getType(),
                    'quantity' => $m->getQuantity(),
                    'date' => $m->getDate()->format('Y-m-d H:i'),
                    'user' => $m->getUser()?->getEmail()
                ], $movements);
                
            }

            public function getLastMovement(Product $product, int $limit = 5 )
            {
                $movements = $this->stockMovementRepository->findLastMovements($product, $limit);

                $data = [];

                foreach ($movements as $movement){
                    $data[]=[
                        'id' => $movement->getId(),
                        'type' => $movement->getType(),
                        'quantity' => $movement->getQuantity(),
                        'date' => $movement->getDate()->format(DATE_ATOM),
                        'user' => $movement->getUser()?->getEmail(),
                    ];
                }

                return $data;

            }

        }