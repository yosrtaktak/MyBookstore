<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un compte ADMIN par défaut
        $admin = new User();
        $admin->setEmail('admin@mybookstore.com');
        $admin->setPrenom('Admin');
        $admin->setNom('Principal');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setTelephone('0123456789');
        $admin->setAdresse('1 rue de l\'Administration');
        $admin->setVille('Paris');
        $admin->setCodePostal('75001');

        $manager->persist($admin);

        // Créer un compte AGENT de test
        $agent = new User();
        $agent->setEmail('agent@mybookstore.com');
        $agent->setPrenom('Agent');
        $agent->setNom('Support');
        $agent->setRoles(['ROLE_AGENT']);
        $agent->setPassword($this->passwordHasher->hashPassword($agent, 'agent123'));
        $agent->setTelephone('0123456788');
        $agent->setAdresse('2 rue du Support');
        $agent->setVille('Lyon');
        $agent->setCodePostal('69001');

        $manager->persist($agent);

        // Créer un compte ABONNE de test
        $abonne = new User();
        $abonne->setEmail('user@mybookstore.com');
        $abonne->setPrenom('Jean');
        $abonne->setNom('Dupont');
        $abonne->setRoles(['ROLE_ABONNE']);
        $abonne->setPassword($this->passwordHasher->hashPassword($abonne, 'user123'));
        $abonne->setTelephone('0123456787');
        $abonne->setAdresse('3 rue du Client');
        $abonne->setVille('Marseille');
        $abonne->setCodePostal('13001');

        $manager->persist($abonne);

        $manager->flush();
    }
}
