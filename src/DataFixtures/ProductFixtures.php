<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\DataFixtures\CategoryFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Category;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $categories = $manager->getRepository(Category::class)->findAll();

        for ($i = 0; $i < 20; $i++) {
            $product = new Product();
            $product->setName("Produit de test n°" . ($i + 1));
            $product->setPrice(mt_rand(100, 1500000) / 100);
            $product->setQuantity(mt_rand(10, 150));

            // associe une catégorie au hasard
            $randomCategory = $categories[array_rand($categories)];
            $product->setCategory($randomCategory);

            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}