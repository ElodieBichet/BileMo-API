<?php

namespace App\DataFixtures;

use Faker\Factory;
use DateTimeImmutable;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ProductFixtures extends Fixture
{
    /**
     * load
     *
     * @param  ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        // available colors for products
        $colors = ['black', 'grey', 'red', 'blue', 'green', 'white'];

        // create fake products
        for ($p = 0; $p < 70; $p++) {
            $product = new Product();

            // force unique product names
            $productNames[$p] = $faker->unique()->words(mt_rand(2, 3), true);
            // force only 3 cents choices for product price
            $cents = [0, 0.45, 0.95];

            $product
                ->setName($productNames[$p])
                ->setCreatedAt(DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-3 weeks', '-2 days')))
                ->setPrice(
                    round($faker->randomFloat(0, 0, 1199) + $cents[mt_rand(0, 2)], 2)
                )
                ->setAvailableQuantity($faker->randomFloat(0, 0, 100));

            // About 30% of the products have a color
            if ($faker->boolean(30)) {
                $product->setColor($faker->randomElement($colors));
            }
            // About 80% of the products have a description
            if ($faker->boolean(80)) {
                $product->setDescription($faker->paragraph(mt_rand(0, 5)));
            }

            $manager->persist($product);
        }

        $manager->flush();
    }
}
