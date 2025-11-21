<?php

namespace App\Controller;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;


#[Route('/api/categories')]
final class CategoryController extends AbstractController
{

 // ---------------- SCHEMAS ----------------
    /**
     * @OA\Schema(
     *     schema="CategoryInput",
     *     type="object",
     *     required={"name"},
     *     @OA\Property(property="name", type="string")
     * )
     *
     * @OA\Schema(
     *     schema="CategoryOutput",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string")
     * )
     */
    
    //get de toutes les categories
    #[Route(methods: ['GET'])]
    #[IsGranted('ROLE_USER', message:'Vous devez être connecté pour accéder à cette ressource.')]
    public function index(CategoryRepository $categoryRepository, SerializerInterface $serializer): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        $data = $serializer->serialize($categories, 'json', ['groups'=>'category:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
}

        //create d'une nouvelle categorie
        #[Route(methods: ['POST'])]
        #[IsGranted('ROLE_STAFF', message:'Seuls les membres du personnel ou les administrateurs peuvent créer des catégories.')]
            public function create(
                Request $request, 
                EntityManagerInterface $entityManager, 
                SerializerInterface $serializer,
                ValidatorInterface $validator): JsonResponse
                {
                try {
                    $category = $serializer->deserialize($request->getContent(), Category::class, 'json');
                }
                catch (\Exception $e) {
                    return new JsonResponse(['message' => 'Format Json invalide.'], Response::HTTP_BAD_REQUEST);
                }

                $errors = $validator->validate($category);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        // Collecte des messages d'erreur détaillés
                        $errorMessages[] = "Champ '{$error->getPropertyPath()}': {$error->getMessage()}";
                    }
                    // Retourne toutes les erreurs de validation
                    return new JsonResponse(['message' => 'Erreur de validation', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
                }

                // 3. Persistance
                $entityManager->persist($category);
                $entityManager->flush();
                
                // 4. Réponse
                $json = $serializer->serialize($category, 'json', ['groups' => 'category:read']);

                return new JsonResponse($json, Response::HTTP_CREATED, [], true);

            }

        //get d'une categorie par son id
            #[Route('/{id}', methods: ['GET'])]
            #[IsGranted('ROLE_USER', message:'Vous devez être connecté pour accéder à cette ressource.')]
            public function show(Category $category, SerializerInterface $serializer): JsonResponse
            {
                $data = $serializer->serialize($category, 'json', ['groups'=>'category:read_products']);
                
                return new JsonResponse($data, Response::HTTP_OK, [], true);
            }

            //modification d'une categorie
            #[Route('/{id}', methods: ['PUT'])]
            #[IsGranted('ROLE_STAFF', message:'Seuls les membres du personnel ou les administrateurs peuvent modifier des catégories.')]
            public function update(Request $request, Category $category, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
            {
                Try{
                    $serializer->deserialize($request->getContent(), Category::class, 'json',[
                        'object_to_populate' => $category,
                        'groups' => 'category:read'
                    ]);

                    if(empty($category->getName())) {
                        return new JsonResponse(['message' => 'Le nom de la catégorie est obligatoire.'], Response::HTTP_BAD_REQUEST);
                    }

                    $entityManager->flush();

                    $data = $serializer->serialize($category, 'json', ['groups'=>'category:read']);

                    return new JsonResponse($data, Response::HTTP_OK, [], true);

                }
                catch(\Exception $e) {
                    return new JsonResponse(['message' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
                }
            }
                

            //suppression d'une categorie
            #[Route('/{id}', methods: ['DELETE'])]
            #[IsGranted('ROLE_ADMIN', message:'Seuls les administrateurs peuvent supprimer des catégories.')]
            public function delete(Category $category, EntityManagerInterface $entityManager): JsonResponse
            {
                if(!$category) {
                    return new JsonResponse(['message' => 'Catégorie non trouvée.'], Response::HTTP_NOT_FOUND);
                }

                $entityManager->remove($category);
                $entityManager->flush();

                return new JsonResponse(null, Response::HTTP_NO_CONTENT);
            }
}