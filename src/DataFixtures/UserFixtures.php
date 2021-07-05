<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\DataFixtures\CustomerFixtures;
use App\Repository\CustomerRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserFixtures extends Fixture
{
    protected $hasher;
    protected $slugger;
    protected $customerRepository;

    public function __construct(
        UserPasswordHasherInterface $hasher,
        CustomerRepository $customerRepository,
        SluggerInterface $slugger
    ) {
        $this->hasher = $hasher;
        $this->slugger = $slugger;
        $this->customerRepository = $customerRepository;
    }

    /**
     * load
     *
     * @param  ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        $customers = $this->customerRepository->findAll();

        // create fake users
        for ($u = 0; $u < 50; $u++) {
            $user = new User;

            $user
                ->setFirstName($faker->firstName())
                ->setLastName($faker->lastName())
                ->setPassword($this->hasher->hashPassword($user, "password"))
                ->setCustomer($faker->randomElement($customers))
                ->setUsername(
                    strtolower(
                        $this->slugger->slug($user->getFirstName())
                            . '.'
                            . $this->slugger->slug($user->getLastName())
                    )
                )
                ->setEmail(
                    strtolower(
                        substr($this->slugger->slug($user->getFirstName()), 0, 1)
                            . '.'
                            . $this->slugger->slug($user->getLastName())
                            . '@'
                            . $this->slugger->slug($user->getCustomer()->getName())
                            . '.com'
                    )
                );

            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * getDependencies
     *
     * @return void
     */
    public function getDependencies()
    {
        // Customer fixtures need to be loaded before User fixtures
        return [
            CustomerFixtures::class,
        ];
    }
}
