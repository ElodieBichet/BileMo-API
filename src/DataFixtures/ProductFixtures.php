<?php

namespace App\DataFixtures;

use Faker\Factory;
use DateTimeImmutable;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        $colors = ['black', 'grey', 'red', 'blue', 'green', 'white'];

        for ($p = 0; $p < 54; $p++) {
            $product = new Product();

            $productNames[$p] = $faker->unique()->words(mt_rand(1, 3), true);

            $product
                ->setName($productNames[$p])
                ->setCreatedAt(DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-3 weeks', '-2 days')))
                ->setPrice($faker->randomFloat(2, 0, 1199.90))
                ->setAvailableQuantity($faker->randomFloat(0, 0, 100));

            if ($faker->boolean(30)) {
                $product->setColor($faker->randomElement($colors));
            }
            if ($faker->boolean(80)) {
                $product->setDescription($faker->paragraph(mt_rand(0, 5)));
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
