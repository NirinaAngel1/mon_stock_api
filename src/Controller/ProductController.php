<?php

namespace App\Controller;

use App\Entity\Product;
use App\Enum\StockMovementType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;
use App\Service\StockService;


#[Route('/api/products')]
class ProductController extends AbstractController
{
    // ---------------- SCHEMAS ----------------
    /**
     * @OA\Schema(
     *     schema="ProductInput",
     *     type="object",
     *     required={"name","price","quantity","category"},
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="price", type="number", format="float"),
     *     @OA\Property(property="quantity", type="integer"),
     *     @OA\Property(property="description", type="string", nullable=true),
     *     @OA\Property(property="category", type="integer", description="ID de la catégorie")
     * )
     *
     * @OA\Schema(
     *     schema="ProductOutput",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="price", type="number", format="float"),
     *     @OA\Property(property="quantity", type="integer"),
     *     @OA\Property(property="description", type="string", nullable=true),
     *     @OA\Property(property="category", type="object", ref="#/components/schemas/CategoryOutput")
     * )
     *
     * @OA\Schema(
     *     schema="CategoryOutput",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string")
     * )
     */

    public function __construct(
        private readonly StockService $stockService,
        private readonly ProductRepository $productRepository,
        private readonly SerializerInterface $serializer
    )
    {}

    #[Route('', name: 'product_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer
        ): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $categoryId = $request->query->getInt('category_id');
        $search = $request->query->get('search');
        $name = $request->query->get('name');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        $filters = [];
        if ($categoryId > 0) $filters['categoryId'] = $categoryId;
        if ($search) $filters['search'] = $search;
        if ($name) $filters['name'] = $name;
        if (is_numeric($minPrice)) $filters['minPrice'] = $minPrice;
        if (is_numeric($maxPrice)) $filters['maxPrice'] = $maxPrice;

        $products = $productRepository->findProductsByCriteria($page, $limit, $filters);

        foreach ($products as $product) {
            $currentStock = $this->stockService->getCurrentStock($product);
            $product->setCurrentStock($currentStock);
        }

        $totalItems = $productRepository->countProductsByCriteria($filters);
        $totalPages = ceil($totalItems / $limit);

        $responseArray = [
            'metadata' => [
                'total_items' => $totalItems,
                'total_pages' => (int)$totalPages,
                'current_page' => $page,
                'limit' => $limit,
                'filtered_by_category_id' => $categoryId,
            ],
            'data' => json_decode($serializer->serialize($products, 'json', ['groups' => 'product:read']), true)
        ];

        $data = $serializer->serialize($responseArray, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'product_create', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator,
        ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $product = $serializer->deserialize($request->getContent(), Product::class, 'json');
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Format JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['category'])) {
            return new JsonResponse(['message' => 'L\'ID de la catégorie est obligatoire.'], Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryRepository->find($data['category'] ?? null);
        if (!$category) {
            return new JsonResponse(['message'=>'Catégorie non trouvée pour l\'ID : ' . $data['category'] ?? 'N/A'], Response::HTTP_BAD_REQUEST);
        }

        $product->setCategory($category);

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = "Champ '{$error->getPropertyPath()}': {$error->getMessage()}";
            }
            return new JsonResponse(['message' => 'Erreur de validation', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        if(isset($data['initial_stock']) && $data['initial_stock']>0 ){
            $initialQuantity = (int) $data['initial_stock'];

            $user = $this->getUser();
            if(!$user){
                return new JsonResponse(['message' => 'Utilisateur non authentifié.'], Response::HTTP_UNAUTHORIZED);
            }

            try{
                $this->stockService->createAdjustmentMovement(
                    $product, 
                    $initialQuantity,
                    StockMovementType::IN,
                    'Stock initial à la création du produit',
                    $user);
            } catch (\Exception $e) {
                return new JsonResponse(['message' => 'Erreur lors de l\'ajout du stock initial : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $product->setCurrentStock($this->stockService->getCurrentStock($product));

        $json = $serializer->serialize($product, 'json', ['groups' => 'product:read']);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $stock = $this->stockService->getCurrentStock($product);
        $product->setCurrentStock($stock);

        $data = $serializer->serialize($product, 'json', ['groups' => 'product:read:item']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'product_update', methods: ['PUT'])]
    #[IsGranted('ROLE_STAFF')]
    public function update(
        Request $request,
        Product $product,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $errors = [];

        if (isset($data['name'])) $product->setName($data['name']);
        if (isset($data['price'])) $product->setPrice($data['price']);
        if (isset($data['description'])) $product->setDescription($data['description']);

        if (isset($data['category'])) {
            $category = $categoryRepository->find($data['category']);
            if (!$category) {
                $errors[] = "La catégorie avec l'ID {$data['category']} n'existe pas.";
            } else {
                $product->setCategory($category);
            }
        }

        $validationErrors = $validator->validate($product);
        foreach ($validationErrors as $error) {
            $errors[] = "Champ '{$error->getPropertyPath()}': {$error->getMessage()}";
        }

        if (!empty($errors)) {
            return new JsonResponse(['message' => 'Erreur de validation', 'errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();
        return new JsonResponse(['message' => 'Produit mis à jour'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'product_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($product);
        $entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}