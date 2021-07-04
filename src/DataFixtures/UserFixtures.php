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

    public function __construct(UserPasswordHasherInterface $hasher, CustomerRepository $customerRepository, SluggerInterface $slugger)
    {
        $this->hasher = $hasher;
        $this->slugger = $slugger;
        $this->customerRepository = $customerRepository;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        $customers = $this->customerRepository->findAll();

        for ($u = 0; $u < 42; $u++) {
            $user = new User;

            $hash = $this->hasher->hashPassword($user, "password");

            $user
                ->setFirstName($faker->firstName())
                ->setLastName($faker->lastName())
                ->setPassword($hash)
                ->setCustomer($faker->randomElement($customers))
                ->setUsername(
                    $this->slugger->slug($user->getFirstName())
                        . '.'
                        . $this->slugger->slug($user->getLastName())
                )
                ->setEmail(
                    $user->getUserIdentifier()
                        . '@'
                        . $this->slugger->slug($user->getCustomer()->getName())
                        . '.com'
                );

            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CustomerFixtures::class,
        ];
    }
}
