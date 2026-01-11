<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Auteur;
use App\Entity\Categorie;
use App\Entity\Configuration;
use App\Entity\Editeur;
use App\Entity\Livre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{
    private array $firstNames = ['Jean', 'Pierre', 'Marie', 'Sophie', 'Lucas', 'Emma', 'Thomas', 'Léa', 'Nicolas', 'Julie'];
    private array $lastNames = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau'];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private ParameterBagInterface $params
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $uploadDir = $this->params->get('kernel.project_dir') . '/public/uploads/livres';
        $sourceDir = $this->params->get('kernel.project_dir') . '/var/fixtures_images';

        // Ensure upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // --- USERS ---
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

        // --- CATEGORIES ---
        $categories = [];
        $catNames = ['Roman', 'Science-Fiction', 'Thriller', 'Fantastique', 'Biographie', 'Histoire', 'Technologie', 'Jeunesse'];
        foreach ($catNames as $name) {
            $cat = new Categorie();
            $cat->setLibelle($name);
            $cat->setDescription("Livres de la catégorie $name");
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // --- EDITEURS ---
        $editeurs = [];
        $editeurNames = ['Gallimard', 'Hachette', 'Flammarion', 'Albin Michel', 'Grasset'];
        foreach ($editeurNames as $name) {
            $editeur = new Editeur();
            $editeur->setNomEditeur($name);
            $editeur->setPays('France');
            $editeur->setAdresse($this->getRandomAddress());
            $editeur->setEmail(strtolower($name) . '@edition.fr');
            $editeur->setTelephone('01' . rand(10000000, 99999999));
            $editeur->setSiteWeb('https://www.' . strtolower($name) . '.fr');
            $manager->persist($editeur);
            $editeurs[] = $editeur;
        }

        // --- AUTEURS ---
        $auteurs = [];
        for ($i = 0; $i < 15; $i++) {
            $auteur = new Auteur();
            $auteur->setPrenom($this->firstNames[array_rand($this->firstNames)]);
            $auteur->setNom($this->lastNames[array_rand($this->lastNames)]);
            $auteur->setNationalite(['Française', 'Anglaise', 'Américaine', 'Belge'][rand(0, 3)]);
            $auteur->setDateNaissance(new \DateTime('-' . rand(25, 80) . ' years'));
            $auteur->setBiographie("Biographie de l'auteur...");
            $manager->persist($auteur);
            $auteurs[] = $auteur;
        }

        // --- LIVRES ---
        for ($i = 1; $i <= 30; $i++) {
            $livre = new Livre();
            $livre->setTitre('Livre ' . $i . ' : ' . ucfirst($this->getRandomWord()) . ' ' . $this->getRandomWord());
            $livre->setIsbn('978-' . rand(1000000000, 9999999999));
            $livre->setNbPages(rand(150, 800));
            $livre->setDateEdition(new \DateTime('-' . rand(0, 10) . ' years'));
            $livre->setNbExemplaires(rand(1000, 50000));
            $livre->setPrix((string)rand(10, 50) . '.' . (rand(0, 1) ? '99' : '50'));
            $livre->setDescription("Description passionnante du livre numéro $i. Une histoire captivante qui vous tiendra en haleine jusqu'à la dernière page.");
            $livre->setLangue(['Français', 'Anglais'][rand(0, 1)]);
            $livre->setStock(rand(0, 50)); // Some out of stock
            
            // Relations
            $livre->setEditeur($editeurs[array_rand($editeurs)]);
            
            $nbAuteurs = rand(1, 2);
            for ($j = 0; $j < $nbAuteurs; $j++) {
                $livre->addAuteur($auteurs[array_rand($auteurs)]);
            }

            $nbCats = rand(1, 3);
            for ($k = 0; $k < $nbCats; $k++) {
                $livre->addCategory($categories[array_rand($categories)]);
            }

            // Image
            $sourceImage = $sourceDir . '/cover_' . rand(1, 5) . '.jpg';
            if (file_exists($sourceImage)) {
                $imageName = 'book_' . uniqid() . '.jpg';
                copy($sourceImage, $uploadDir . '/' . $imageName);
                $livre->setImageCouverture($imageName);
            }

            $manager->persist($livre);
        }

        // --- CONFIGURATION ---
        $stockThreshold = new Configuration();
        $stockThreshold->setSettingKey('stock_alert_threshold');
        $stockThreshold->setSettingValue('5');
        $manager->persist($stockThreshold);

        $manager->flush();
    }

    private function getRandomAddress(): string
    {
        return rand(1, 150) . ' rue ' . $this->lastNames[array_rand($this->lastNames)] . ', ' . rand(75000, 75020) . ' Paris';
    }

    private function getRandomWord(): string
    {
        $words = ['Mystère', 'Voyage', 'Ombre', 'Lumière', 'Destin', 'Secret', 'Monde', 'Aveni', 'Passé', 'Rêve', 'Silence', 'Écho'];
        return $words[array_rand($words)];
    }
}
