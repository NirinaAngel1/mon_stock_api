<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Stmt\TryCatch;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN', message:'Accès réservé aux administrateurs.')]
final class UserController extends AbstractController
{
    #[Route(methods: ['GET'])]
    public function index(UserRepository $useRepository, SerializerInterface $serializer): Response
    {
        $users = $useRepository->findAll();

        $data = $serializer->serialize($users, 'json', ['groups' => 'user:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
    

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        Request $request, 
        User $user, 
        EntityManagerInterface $em, 
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher):JsonResponse
    {
        Try{
            $data = json_decode($request->getContent(), true);

            if(isset($data['email']) && !empty($data['email'])){
                $user->setEmail($data['email']);
            }

            if(isset($data['password']) && !empty($data['password'])){
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            if(isset($data['roles']) && is_array($data['roles'])){
                $user->setRoles($data['roles']);
            }

            $em->persist($user);
            $em->flush();

            $responseData = $serializer->serialize($user, 'json', ['groups' => 'user:read']);

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);

        }
        catch(\Exception $e){
            return new JsonResponse(['message' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em):JsonResponse
    {
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
