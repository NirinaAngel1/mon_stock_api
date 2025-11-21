<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{

    public const ADMIN_USER_REFERENCE = 'user-admin';
    public const STAFF_USER_REFERENCE = 'user-staff';
    public const SIMPLE_USER_REFERENCE = 'user-simple';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $user1 = new User();
        $user1->setEmail('admin@mon-api-stock.fr');
        $user1->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user1,
            'mdp_admin'
        );
        $user1->setPassword($hashedPassword);
        $this->addReference(self::ADMIN_USER_REFERENCE, $user1);

        $user2 = new User();
        $user2->setEmail('utilisateur_staff@mon-api-stock.fr');
        $user2->setRoles(['ROLE_STAFF']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user2,
            'mdp_staff'
        );
        $user2->setPassword($hashedPassword);
        $this->addReference(self::STAFF_USER_REFERENCE, $user2);

        $user3 = new User();
        $user3->setEmail('utilisateur@mon-api-stock.fr');
        $user3->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user3,
            'mdp_staff'
        );
        $user3->setPassword($hashedPassword);
        $this->addReference(self::SIMPLE_USER_REFERENCE, $user3);

        $manager->persist($user1);
        $manager->persist($user2);
        $manager->persist($user3);

        $manager->flush();
    }
}
