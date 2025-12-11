<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\StockService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/orders')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly StockService $stockService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer
    ) {}
    #[Route('/order', name: 'app_order')]
    public function index(
        OrderRepository $orderRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $orders = $orderRepository->findAll();
        $json_data = $this->$serializer->serialize($orders, 'json', ['groups' => 'order:read']);
        return new JsonResponse($json_data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}/validate', name: 'order_validate', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')] // Seul le personnel peut valider une commande client (vente)
    public function validate(Order $order): JsonResponse
    {
        return new JsonResponse(['message' => 'Commande validée et stock mis à jour.'], Response::HTTP_OK);
    }
}
