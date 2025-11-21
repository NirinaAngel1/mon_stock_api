<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORIES = [
        'Électronique',
        'Vêtements',
        'Alimentation',
        'Sport & Loisirs',
        'Maison & Jardin',
    ];

    public function load(ObjectManager $manager):void
    {
        foreach(self::CATEGORIES as $index=>$categoryName){
            $category = new Category();
            $category->setName($categoryName);

            $manager->persist($category);

            $this->addReference("category_" . $index, $category);
        }
        $manager->flush();
    }
}