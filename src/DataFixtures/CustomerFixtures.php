<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;
use DateTimeImmutable;
use App\Entity\Customer;
use Faker\Provider\fr_FR\Company;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $fakerGenerator = new Generator();
        $fakerGenerator->addProvider(new Company($fakerGenerator));

        for ($c = 0; $c < 4; $c++) {
            $customer = new Customer;

            $customer
                ->setName(ucfirst($faker->words(mt_rand(1, 3), true)))
                ->setIsAllowed($faker->boolean(80))
                ->setSiret($fakerGenerator->siret());

            if ($faker->boolean(50)) {
                $customer->setExpireAt(DateTimeImmutable::createFromMutable($faker->dateTimeBetween('+6 months', '+2 years')));
            }

            $manager->persist($customer);
        }

        $manager->flush();
    }
}
