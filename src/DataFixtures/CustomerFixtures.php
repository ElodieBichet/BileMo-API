<?php

namespace App\DataFixtures;

use Faker\Factory;
use DateTimeImmutable;
use App\Entity\Customer;
use Faker\Provider\fr_FR\Company;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CustomerFixtures extends Fixture
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
        $faker->addProvider(new Company($faker));

        // create fake customers
        for ($c = 0; $c < 4; $c++) {
            $customer = new Customer;

            $customer
                ->setName(ucfirst($faker->words(mt_rand(2, 3), true)))
                ->setIsAllowed($faker->boolean(70))
                ->setSiret($faker->siret());

            // About 50% of the customers have an expiration date
            if ($faker->boolean(50)) {
                $customer->setExpireAt(DateTimeImmutable::createFromMutable($faker->dateTimeBetween('+6 months', '+2 years')));
            }

            $manager->persist($customer);
        }

        $manager->flush();
    }
}
