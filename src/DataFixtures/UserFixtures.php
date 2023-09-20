<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager)
    {
        $adminUser = new User();
        $adminUser->setUsername('admin');

        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'password');

        $adminUser->setPassword($hashedPassword);
        $adminUser->setRoles(['ROLE_ADMIN']);

        $manager->persist($adminUser);
        $manager->flush();
    }
}
