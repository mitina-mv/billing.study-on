<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt']
        ]);
        $hasher = $factory->getPasswordHasher('common');
        
        $user = new User();
        $user->setEmail('user@email.example')
            ->setPassword(
                $hasher->hash('user@email.example')
            );
        $user->setBalance(0.0);
        $manager->persist($user);

        $user_admin = new User();
        $user_admin->setEmail('user_admin@email.example')
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setPassword(
                $hasher->hash('user_admin@email.example')
            );
        $user_admin->setBalance(1000.0);
        $manager->persist($user_admin);

        $manager->flush();
    }
}
