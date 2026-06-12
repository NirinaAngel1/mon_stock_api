<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/suppliers')]
#[IsGranted('ROLE_STAFF')]
final class SupplierController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}
    #[Route('', name: 'app_Supplier_index', methods: ['GET'])]
    public function index(SupplierRepository $supplierRepository): JsonResponse
    {
        $suppliers = $supplierRepository->findAll();

        $json_data = $this->serializer->serialize(
            $suppliers,
            'json',
            ['groups' => 'supplier:read']
        );

        return new JsonResponse($json_data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_Supplier_show', methods: ['GET'])]
    public function show(Supplier $supplier): JsonResponse
    {
        $json_data = $this->serializer->serialize(
            $supplier,
            'json',
            ['groups' => 'supplier:read']
        );

        return new JsonResponse($json_data, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'app_Supplier_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getContent();
        $supplier = $this->serializer->deserialize(
            $data,
            Supplier::class,
            'json'
        );

        $errors = $this->validator->validate($supplier);
        if (count($errors) > 0) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Données du Fournisseur invalides',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Fournisseur créé avec succès',
            'supplier_id' => $supplier->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_Supplier_update', methods: ['PUT'])]
    public function update(Supplier $supplier, Request $request): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Supplier::class,
            'json',
            ['object_to_populate' => $supplier]
        );

        $errors = $this->validator->validate($supplier);
        if (count($errors) > 0) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Données du Fournisseur invalides',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Fournisseur mis à jour avec succès'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_Supplier_delete', methods: ['DELETE'])]
    public function delete(Supplier $supplier): JsonResponse
    {
        $this->entityManager->remove($supplier);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Fournisseur supprimé avec succès'
        ], Response::HTTP_OK);
    }
}
