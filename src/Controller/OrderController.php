<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Enum\OrderType;
use App\Enum\StockMovementType;
use App\Repository\OrderRepository;
use App\Service\StockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Product;
use App\Entity\OrderLine;
use App\Security\Voter\OrderVoter;
use Doctrine\DBAL\LockMode;

#[Route('/api/orders')]
final class OrderController extends AbstractController
{
    public function __construct(
        // private readonly OrderService $orderService,
        private readonly StockService $stockService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer
    ) {}
    #[Route(name: 'app_all_orders', methods:['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        OrderRepository $orderRepository,
    ): JsonResponse
    {
        //récupération de toutes les commandes de l'utilisateur connecté sauf pour ADMIN
        if($this->isGranted('ROLE_ADMIN')){
            $orders = $orderRepository->findAll();
        }else{
        $orders = $orderRepository->findBy([
            'userId' => $this->getUser()
        ]);
        }

        //filtre pour les utilisateurs non ADMIN, on vérifie que l'utilisateur a le droit de voir chaque commande
        $orders = array_filter($orders, fn($order) => $this->isGranted(OrderVoter::VIEW, $order));

        $json_data = $this->serializer->serialize(
            $orders,
            'json',
            ['groups' => 'order:read']
        );
        return new JsonResponse($json_data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name:'app_getsingle_order', methods:['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getSingle(Order $order):JsonResponse
    {
        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $order);

        $json_data = $this->serializer->serialize(
            $order,
            'json',
            ['groups'=>'order:read']
        );

        if(!$json_data){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Cette commande est inexistante.'
            ], 400);
        }
        return new JsonResponse($json_data, Response::HTTP_OK,[], true);
    }

    #[Route('/{id}/submit', name:'app_order_submit', methods:['POST'])]
    #[isGranted('ROLE_STAFF')]
    public function submitOrder(Order $order):JsonResponse
    {
        $this->denyAccessUnlessGranted(OrderVoter::EDIT, $order);

        if(in_array($order->getStatus(),[
            OrderStatus::PENDING,
            OrderStatus::CANCELLED,
            OrderStatus::COMPLETED
        ])){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Commande déjà validée ou annulée'
            ],400);
        }

        $order->setStatus(OrderStatus::PENDING);
        $this->entityManager->flush();

        return new JsonResponse([
            'status'=>'success',
            'message'=>'Commande soumise et prête à être validée'
        ]);
    }

    #[Route('/{id}/validate', name: 'app_order_validate', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')] // Seul le personnel peut valider une commande client (vente)
    public function validate(Order $order): JsonResponse
    {

        $this->denyAccessUnlessGranted(OrderVoter::VALIDATE, $order);

        if(in_array($order->getStatus(),[
            OrderStatus::CANCELLED,
            OrderStatus::COMPLETED
        ])){
            return new JsonResponse([
                'status'=>'error',
                'message'=>"Commande déjà finalisée ou annulée"
            ], 400);
        }

        if ($order->getStatus() !== OrderStatus::PENDING){
            return new JsonResponse([
                'status'=>'error',
                "message"=>"Seules les commandes en attente peuvent être validées"
            ], 400);
        }

        if($order->getType()===OrderType::SALES){
        foreach($order->getOrderLines() as $line){
            $product = $line->getProduct();
            $qty = $line->getQuantity();

            //LOCK le produit pour sécuriser la concurrence sur le stock
            $this->entityManager->lock($line->getProduct(), LockMode::PESSIMISTIC_WRITE);

             $available = $this->stockService->getCurrentStock($product);

             if($available < $qty){
                return new JsonResponse([
                    'message'=>"Stock insuffisant pour {$product->getName()}",
                    'available'=>$available,
                    'required'=>$qty
                ], 400);
            }

            $available = $this->stockService->getCurrentStock($product);

                if($available < $qty){
                    return new JsonResponse([
                        'message'=>"Stock insuffisant pour {$product->getName()}",
                        'available'=>$available,
                        'required'=>$qty
                    ], 400);
                }
            }
        }

        foreach($order->getOrderLines() as $line){
            $movementType = $order->getType() === OrderType::SALES
            ? StockMovementType::OUT
            : StockMovementType::IN;

            try{
                $this->stockService->createOrderMovement(
                    $line,
                    $movementType,
                    $this->getUser()
                );
            }catch(\DomainException $e){
                return $this->json(['error'=> $e->getMessage()], 400);
            }   
        }
        $order->setStatus(OrderStatus::COMPLETED);
        $this->entityManager->flush();

        return new JsonResponse([
            'status'=>'success',
            'message'=>"Commande validé et stock mis à jour"
        ]);
    }

    #[Route(name:'app_create_order', methods:['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function createOrder(Request $request):JsonResponse
    {

        $data = $request->getPayload()->all();

        if(!$data){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Aucune donnée reçue ou Json invalide'
            ], 400);
        }

        //validation du type de commande
        if(!isset($data['type']) || !in_array($data['type'],
        [OrderType::PURCHASES->value,
        OrderType::SALES->value])){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Type de commande invalide ou inexistant'
            ],400);
        }

        //validation de la ligne de commande
        if(!isset($data['lines']) ||
        !is_array($data['lines']) ||
        count($data['lines'])===0){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Ligne de commande invalide'
            ], 400);
        }

            try {
            $orderType = OrderType::from($data['type']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status'=>'error',
                'message' => 'Type de commande inexistant dans la base'
            ], 400);
        }

        $order = new Order();
        $order->setType($orderType);
        $order->setStatus(OrderStatus::DRAFT);
        $order->setDate(new \DateTimeImmutable());
        $order->setUserId($this->getUser());
        $order->setReference("COM-".date('Ymd-His').'-'.random_int(001, 999));

        $totalAmount = 0;

        // Traitement des lignes
        foreach ($data['lines'] as $lineData) {
            $productId = $lineData['product'] ?? null;
            $quantity = $lineData['quantity'] ?? null;

            if(!$productId || !$quantity || $quantity <= 0){
                return new JsonResponse([
                    'status'=>'error',
                    'message'=>'Produit ou quantité invalide dans les lignes'
                ], 400);
            }

            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return new JsonResponse([
                    'status'=>'error',
                    'message' => "Produit ID {$lineData['product']} introuvable"
                ], 404);
            }

            if ($quantity <= 0) {
                return new JsonResponse([
                    'status'=>'error',
                    'message' => "Quantité invalide pour le produit {$product->getName()}"
                ], 400);
            }

            $orderLine = new OrderLine();
            $orderLine->setProduct($product);
            $orderLine->setQuantity($quantity);
            $orderLine->setUnitPrice($product->getPrice() ?? 0);
            $orderLine->setOrder($order);

            $this->entityManager->persist($orderLine);

            $order->addOrderLine($orderLine);
            $totalAmount+=$quantity*$orderLine->getUnitPrice();
        }

        $order->setTotalAmount((string)$totalAmount);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return new JsonResponse([
            'status'=>'success',
            'message' => 'Commande créée avec succès',
            'order_id' => $order->getId(),
            'reference'=>$order->getReference(),
            'total_amount'=>$order->getTotalAmount(),
        ], 201);
    }


    #[Route("/{id}/cancel",name:"app_cancel_order", methods:['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function orderCanceled(Order $order):JsonResponse
    {

        $this->denyAccessUnlessGranted(OrderVoter::CANCEL, $order);

        if($order->getStatus()===OrderStatus::CANCELLED){
            return new JsonResponse([
                'status'=>'error',
                'message'=>"Cette commande a été déjà annulée"
            ], 400);
        }

        if($order->getStatus()===OrderStatus::COMPLETED){
            return new JsonResponse([
                'status'=>'error',
                'message'=>"Opération impossible, commande déjà validée"
            ], 400);
        }

        $order->setStatus(OrderStatus::CANCELLED);
        $this->entityManager->flush();

        return new JsonResponse([
            'status'=>'success',
            'message'=>"Commande annulée avec succès"
        ]);
    }

    #[Route('/{id}', name:'app_order_delete', methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteOrder(Order $order):JsonResponse
    {

        $this->denyAccessUnlessGranted(OrderVoter::EDIT, $order);

        if($order->getStatus() === OrderStatus::COMPLETED){
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Cette commande est déjà validée, impossible de la supprimer'
            ], 400);
        }

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return new JsonResponse([
            'status'=>'success',
            "message"=>"Commande supprimée avec succès"
        ], 200);
    }
}