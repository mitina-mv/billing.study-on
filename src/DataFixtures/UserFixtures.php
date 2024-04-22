<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@email.example')
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    'user@email.example'
                )
            );
        $user->setBalance(0.0);

        $user_admin = new User();
        $user_admin->setEmail('user_admin@email.example')
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    'user_admin@email.example'
                )
            );
        $user_admin->setBalance(2000.0);
        $manager->persist($user);
        $manager->persist($user_admin);
        $manager->flush();
    }
}
