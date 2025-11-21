<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('/api/register')]
class AuthController extends AbstractController
{
    #[Route(methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jWTToken):JsonResponse
    {
        try{
            $data = json_decode($request->getContent(), true);

            // Validation des champs obligatoires (email et mdp)
            if(!isset($data['email']) || empty($data['email']) || !isset($data['password']) || empty($data['password'])){
                return new JsonResponse(['message'=>'Les champs email et mot de passe sont obligatoires.'], Response::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setUsername($data['username'] ?? null);

            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            
            $roles = $data['roles'] ?? ['ROLE_USER'];
            $user->setRoles($roles);

            $em->persist($user);
            $em->flush();

            $token = $jWTToken->create($user);
            
            $responseData = [
                'user' => json_decode($serializer->serialize($user, 'json', ['groups' => 'user:read']), true),
                'token' => $token
            ];

            return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);

        }
        catch(\Exception $e){
            return new JsonResponse(['message' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }
    }
}
