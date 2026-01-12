<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Auteur;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\LigneCommande;
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
        
        // Real famous authors
        $realAuthors = [
            ['Victor', 'Hugo', 'Française', '1802-02-26'],
            ['Albert', 'Camus', 'Française', '1913-11-07'],
            ['J.K.', 'Rowling', 'Britannique', '1965-07-31'],
            ['George', 'Orwell', 'Britannique', '1903-06-25'],
            ['Agatha', 'Christie', 'Britannique', '1890-09-15'],
            ['Dan', 'Brown', 'Américaine', '1964-06-22'],
            ['Stephen', 'King', 'Américaine', '1947-09-21'],
            ['Antoine de', 'Saint-Exupéry', 'Française', '1900-06-29'],
            ['Harper', 'Lee', 'Américaine', '1926-04-28'],
            ['F. Scott', 'Fitzgerald', 'Américaine', '1896-09-24'],
            ['Gabriel García', 'Márquez', 'Colombienne', '1927-03-06'],
            ['Paulo', 'Coelho', 'Brésilienne', '1947-08-24'],
        ];
        
        foreach ($realAuthors as $authorData) {
            $auteur = new Auteur();
            $auteur->setPrenom($authorData[0]);
            $auteur->setNom($authorData[1]);
            $auteur->setNationalite($authorData[2]);
            $auteur->setDateNaissance(new \DateTime($authorData[3]));
            $auteur->setBiographie("Auteur célèbre de renommée internationale.");
            $manager->persist($auteur);
            $auteurs[$authorData[1]] = $auteur;
        }

        // --- LIVRES RÉELS ---
        $realBooks = [
            [
                'titre' => 'Les Misérables',
                'auteur' => 'Hugo',
                'isbn' => '978-2-07-036654-3',
                'pages' => 1900,
                'annee' => 1862,
                'prix' => '29.90',
                'description' => "Chef-d'œuvre de Victor Hugo, ce roman retrace le destin de Jean Valjean, ancien bagnard en quête de rédemption, dans la France du XIXe siècle.",
                'categorie' => 'Roman',
                'stock' => 15,
                'langue' => 'Français',
                'image_url' => 'https://images-na.ssl-images-amazon.com/images/I/51VgbH4YqVL._SX331_BO1,204,203,200_.jpg'
            ],
            [
                'titre' => "L'Étranger",
                'auteur' => 'Camus',
                'isbn' => '978-2-07-036002-2',
                'pages' => 185,
                'annee' => 1942,
                'prix' => '8.50',
                'description' => "Roman d'Albert Camus racontant l'histoire de Meursault, un homme indifférent qui commet un meurtre absurde en Algérie.",
                'categorie' => 'Roman',
                'stock' => 22,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/41p5QTaUwbL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => "Harry Potter à l'école des sorciers",
                'auteur' => 'Rowling',
                'isbn' => '978-2-07-054120-2',
                'pages' => 320,
                'annee' => 1997,
                'prix' => '18.90',
                'description' => "Premier tome de la saga Harry Potter. Harry découvre qu'il est un sorcier et entre à Poudlard, l'école de sorcellerie.",
                'categorie' => 'Fantastique',
                'stock' => 35,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51DnF4SQFOL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => '1984',
                'auteur' => 'Orwell',
                'isbn' => '978-2-07-036822-6',
                'pages' => 438,
                'annee' => 1949,
                'prix' => '9.50',
                'description' => "Dans un monde totalitaire dominé par Big Brother, Winston Smith tente de résister à l'oppression du Parti.",
                'categorie' => 'Science-Fiction',
                'stock' => 18,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/41E0iXNxzfL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Le Crime de l\'Orient-Express',
                'auteur' => 'Christie',
                'isbn' => '978-2-253-00341-6',
                'pages' => 256,
                'annee' => 1934,
                'prix' => '7.90',
                'description' => "Hercule Poirot enquête sur un meurtre commis dans le célèbre Orient-Express. Un classique du roman policier.",
                'categorie' => 'Thriller',
                'stock' => 12,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51X75vI2OXL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Da Vinci Code',
                'auteur' => 'Brown',
                'isbn' => '978-2-253-11469-9',
                'pages' => 574,
                'annee' => 2003,
                'prix' => '9.90',
                'description' => "Robert Langdon, professeur en symbologie, est entraîné dans une enquête haletante autour d'un mystère millénaire.",
                'categorie' => 'Thriller',
                'stock' => 25,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51pxZ9dEoNL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Shining',
                'auteur' => 'King',
                'isbn' => '978-2-253-15177-9',
                'pages' => 512,
                'annee' => 1977,
                'prix' => '10.90',
                'description' => "Jack Torrance devient gardien d'un hôtel isolé pour l'hiver. Mais l'hôtel Overlook cache de terribles secrets.",
                'categorie' => 'Thriller',
                'stock' => 8,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51eV9C6Z0LL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Le Petit Prince',
                'auteur' => 'Saint-Exupéry',
                'isbn' => '978-2-07-061275-8',
                'pages' => 96,
                'annee' => 1943,
                'prix' => '6.90',
                'description' => "Conte poétique et philosophique racontant la rencontre d'un aviateur et d'un petit prince venu d'une autre planète.",
                'categorie' => 'Jeunesse',
                'stock' => 42,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/41X2KMN2X+L._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Ne tirez pas sur l\'oiseau moqueur',
                'auteur' => 'Lee',
                'isbn' => '978-2-253-14845-8',
                'pages' => 480,
                'annee' => 1960,
                'prix' => '8.90',
                'description' => "Dans l'Alabama des années 1930, Atticus Finch, avocat, défend un homme noir accusé injustement.",
                'categorie' => 'Roman',
                'stock' => 14,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51Z9p5AecCL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Gatsby le Magnifique',
                'auteur' => 'Fitzgerald',
                'isbn' => '978-2-07-037657-3',
                'pages' => 254,
                'annee' => 1925,
                'prix' => '7.50',
                'description' => "Portrait de la société américaine des années 20 à travers le personnage mystérieux de Jay Gatsby.",
                'categorie' => 'Roman',
                'stock' => 11,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/41iers+PKOL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'Cent ans de solitude',
                'auteur' => 'Márquez',
                'isbn' => '978-2-02-134234-8',
                'pages' => 448,
                'annee' => 1967,
                'prix' => '9.20',
                'description' => "L'histoire épique de la famille Buendía sur plusieurs générations dans le village imaginaire de Macondo.",
                'categorie' => 'Roman',
                'stock' => 9,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51-Uu1+XnoL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
            [
                'titre' => 'L\'Alchimiste',
                'auteur' => 'Coelho',
                'isbn' => '978-2-290-35383-6',
                'pages' => 253,
                'annee' => 1988,
                'prix' => '7.90',
                'description' => "Fable philosophique sur un jeune berger andalou parti à la recherche d'un trésor près des pyramides d'Égypte.",
                'categorie' => 'Roman',
                'stock' => 28,
                'langue' => 'Français',
                'image_url' => 'https://m.media-amazon.com/images/I/51Z0nLAfLmL._SY291_BO1,204,203,200_QL40_ML2_.jpg'
            ],
        ];

        foreach ($realBooks as $bookData) {
            $livre = new Livre();
            $livre->setTitre($bookData['titre']);
            $livre->setIsbn($bookData['isbn']);
            $livre->setNbPages($bookData['pages']);
            
            $dateEdition = new \DateTime();
            $dateEdition->setDate($bookData['annee'], 1, 1);
            $livre->setDateEdition($dateEdition);
            
            $livre->setNbExemplaires(rand(5000, 50000));
            $livre->setPrix($bookData['prix']);
            $livre->setDescription($bookData['description']);
            $livre->setLangue($bookData['langue']);
            $livre->setStock($bookData['stock']);
            
            // Relations
            $livre->setEditeur($editeurs[array_rand($editeurs)]);
            $livre->addAuteur($auteurs[$bookData['auteur']]);
            
            // Add category
            foreach ($categories as $cat) {
                if ($cat->getLibelle() === $bookData['categorie']) {
                    $livre->addCategory($cat);
                    break;
                }
            }

            // Download and save book cover image
            if (isset($bookData['image_url'])) {
                try {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
                        ]
                    ]);
                    $imageContent = @file_get_contents($bookData['image_url'], false, $context);
                    if ($imageContent !== false) {
                        $imageName = 'book_' . uniqid() . '.jpg';
                        file_put_contents($uploadDir . '/' . $imageName, $imageContent);
                        $livre->setImageCouverture($imageName);
                    }
                } catch (\Exception $e) {
                    // Si le téléchargement échoue, on continue sans image
                }
            }

            $manager->persist($livre);
        }
        
        $manager->flush(); // Flush books to get their IDs
        
        // Get all books for orders
        $allLivres = $manager->getRepository(Livre::class)->findAll();

        // --- COMMANDES ---
        $statuts = ['EN_ATTENTE', 'EN_COURS', 'EXPEDIEE', 'LIVREE', 'ANNULEE'];
        $users = [$admin, $agent, $abonne];
        
        for ($i = 0; $i < 8; $i++) {
            $commande = new Commande();
            $commande->setUser($users[array_rand($users)]);
            $commande->setDateCommande(new \DateTime('-' . rand(1, 60) . ' days'));
            $commande->setStatut($statuts[array_rand($statuts)]);
            
            // Adresse de livraison
            $commande->setAdresseLivraison(rand(1, 100) . ' rue ' . $this->lastNames[array_rand($this->lastNames)]);
            $commande->setVilleLivraison(['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice'][rand(0, 4)]);
            $commande->setCodePostalLivraison((string)rand(10000, 99999));
            
            // Add 1-4 books to the order
            $nbLivres = rand(1, 4);
            $montantTotal = 0;
            $selectedBooks = array_rand($allLivres, min($nbLivres, count($allLivres)));
            if (!is_array($selectedBooks)) {
                $selectedBooks = [$selectedBooks];
            }
            
            foreach ($selectedBooks as $bookIndex) {
                $livre = $allLivres[$bookIndex];
                $quantite = rand(1, 3);
                
                $ligneCommande = new LigneCommande();
                $ligneCommande->setLivre($livre);
                $ligneCommande->setQuantite($quantite);
                $ligneCommande->setPrixUnitaire($livre->getPrix());
                $ligneCommande->setCommande($commande);
                
                $montantTotal += (float)$livre->getPrix() * $quantite;
                
                $manager->persist($ligneCommande);
            }
            
            $commande->setMontantTotal((string)number_format($montantTotal, 2, '.', ''));
            $manager->persist($commande);
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
