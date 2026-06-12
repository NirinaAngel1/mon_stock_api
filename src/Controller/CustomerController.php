<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customers')]
#[IsGranted('ROLE_STAFF')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}
    #[Route('', name: 'app_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): JsonResponse
    {
        $customers = $customerRepository->findAll();

        $json_data = $this->serializer->serialize(
            $customers,
            'json',
            ['groups' => 'customer:read']
        );

        return new JsonResponse($json_data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(Customer $customer): JsonResponse
    {
        $json_data = $this->serializer->serialize(
            $customer,
            'json',
            ['groups' => 'customer:read']
        );

        return new JsonResponse($json_data, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'app_customer_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->getContent();
        $customer = $this->serializer->deserialize(
            $data,
            Customer::class,
            'json'
        );

        $errors = $this->validator->validate($customer);
        if (count($errors) > 0) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Données du client invalides',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Client créé avec succès',
            'customer_id' => $customer->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_customer_update', methods: ['PUT'])]
    public function update(Customer $customer, Request $request): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Customer::class,
            'json',
            ['object_to_populate' => $customer]
        );

        $errors = $this->validator->validate($customer);
        if (count($errors) > 0) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Données du client invalides',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Client mis à jour avec succès'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['DELETE'])]
    public function delete(Customer $customer): JsonResponse
    {
        $this->entityManager->remove($customer);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'Client supprimé avec succès'
        ], Response::HTTP_OK);
    }
}
