<?php

namespace App\Repository;

use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Livre>
 */
class LivreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livre::class);
    }

    /**
     * Trouve les livres avec filtres, tri et pagination
     * 
     * @param int|null $categorieId ID de la catégorie pour filtrer
     * @param int|null $editeurId ID de l'éditeur pour filtrer
     * @param float|null $prixMin Prix minimum
     * @param float|null $prixMax Prix maximum
     * @param string|null $search Terme de recherche (titre, auteur, ISBN)
     * @param string $orderBy Champ de tri (newest, price_asc, price_desc, title)
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return array ['items' => Livre[], 'total' => int, 'pages' => int]
     */
    public function findWithFiltersAndPagination(
        ?int $categorieId = null,
        ?int $editeurId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $search = null,
        string $orderBy = 'newest',
        int $page = 1,
        int $limit = 12
    ): array {
        // Première requête pour obtenir les IDs des livres qui correspondent aux critères
        $qb = $this->createQueryBuilder('l')
            ->select('l.id')
            ->where('l.stock > 0'); // Seulement les livres disponibles

        // Filtre par catégorie
        if ($categorieId) {
            $qb->leftJoin('l.categories', 'c')
               ->andWhere('c.id = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }

        // Filtre par éditeur
        if ($editeurId) {
            $qb->andWhere('l.editeur = :editeurId')
               ->setParameter('editeurId', $editeurId);
        }

        // Filtre par prix minimum
        if ($prixMin !== null) {
            $qb->andWhere('l.prix >= :prixMin')
               ->setParameter('prixMin', $prixMin);
        }

        // Filtre par prix maximum
        if ($prixMax !== null) {
            $qb->andWhere('l.prix <= :prixMax')
               ->setParameter('prixMax', $prixMax);
        }

        // Recherche par titre, auteur ou ISBN
        if ($search) {
            $qb->leftJoin('l.auteurs', 'a')
               ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('l.titre', ':search'),
                    $qb->expr()->like('l.isbn', ':search'),
                    $qb->expr()->like('CONCAT(a.prenom, \' \', a.nom)', ':search')
                )
            )->setParameter('search', '%' . $search . '%');
        }

        // Grouper par l.id pour éviter les doublons dus aux jointures
        $qb->groupBy('l.id');

        // Tri
        switch ($orderBy) {
            case 'price_asc':
                $qb->addSelect('l.prix')->orderBy('l.prix', 'ASC');
                break;
            case 'price_desc':
                $qb->addSelect('l.prix')->orderBy('l.prix', 'DESC');
                break;
            case 'title':
                $qb->addSelect('l.titre')->orderBy('l.titre', 'ASC');
                break;
            case 'newest':
            default:
                $qb->orderBy('l.id', 'DESC');
                break;
        }

        // Compter le total
        $allIds = $qb->getQuery()->getResult();
        $total = count($allIds);

        // Appliquer la pagination sur les IDs
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $ids = $qb->getQuery()->getResult();
        
        // Si aucun résultat, retourner un tableau vide
        if (empty($ids)) {
            return [
                'items' => [],
                'total' => 0,
                'pages' => 0,
                'currentPage' => $page,
                'perPage' => $limit,
            ];
        }

        // Extraire les IDs du résultat
        $livreIds = array_map(function($row) {
            return is_array($row) ? $row['id'] : $row;
        }, $ids);

        // Deuxième requête pour récupérer les livres complets avec leurs relations
        $qb2 = $this->createQueryBuilder('l')
            ->leftJoin('l.auteurs', 'a')->addSelect('a')
            ->leftJoin('l.categories', 'c')->addSelect('c')
            ->leftJoin('l.editeur', 'e')->addSelect('e')
            ->where('l.id IN (:ids)')
            ->setParameter('ids', $livreIds);

        // Réappliquer le tri sur la requête finale
        switch ($orderBy) {
            case 'price_asc':
                $qb2->orderBy('l.prix', 'ASC');
                break;
            case 'price_desc':
                $qb2->orderBy('l.prix', 'DESC');
                break;
            case 'title':
                $qb2->orderBy('l.titre', 'ASC');
                break;
            case 'newest':
            default:
                $qb2->orderBy('l.id', 'DESC');
                break;
        }

        $items = $qb2->getQuery()->getResult();
        $pages = (int) ceil($total / $limit);

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'currentPage' => $page,
            'perPage' => $limit,
        ];
    }

    /**
     * Trouve les derniers livres ajoutés
     * 
     * @param int $limit Nombre de livres à retourner
     * @return Livre[]
     */
    public function findLatestBooks(int $limit = 8): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.stock > 0')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les livres en vedette (avec stock disponible)
     * 
     * @param int $limit Nombre de livres à retourner
     * @return Livre[]
     */
    public function findFeaturedBooks(int $limit = 4): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.stock > 0')
            ->orderBy('l.nbExemplaires', 'DESC') // Les livres avec plus d'exemplaires
            ->addOrderBy('l.dateEdition', 'DESC') // Les plus récents
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve des livres similaires (même catégorie)
     * 
     * @param Livre $livre Le livre de référence
     * @param int $limit Nombre de livres similaires
     * @return Livre[]
     */
    public function findSimilarBooks(Livre $livre, int $limit = 4): array
    {
        $categories = $livre->getCategories();
        
        if ($categories->isEmpty()) {
            return [];
        }

        $qb = $this->createQueryBuilder('l')
            ->where('l.id != :livreId')
            ->andWhere('l.stock > 0')
            ->setParameter('livreId', $livre->getId());

        // Filtrer par les catégories du livre
        foreach ($categories as $index => $categorie) {
            $paramName = 'categorie' . $index;
            $qb->orWhere(':' . $paramName . ' MEMBER OF l.categories')
               ->setParameter($paramName, $categorie);
        }

        return $qb->orderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    /**
     * Compte les livres en rupture de stock imminent
     */
    public function countLowStock(int $threshold = 5): int
    {
        return $this->createQueryBuilder('l')
            ->select('count(l.id)')
            ->where('l.stock < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les livres en rupture de stock imminent
     * @return Livre[]
     */
    public function findLowStock(int $threshold = 5, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.stock < :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('l.stock', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les livres les plus vendus
     * @return array [{livre: Livre, totalVendu: int, chiffreAffaire: float}]
     */
    public function findBestSellers(int $limit = 5): array
    {
        $results = $this->createQueryBuilder('l')
            ->select('l as livre', 'SUM(lc.quantite) as totalVendu', 'SUM(lc.quantite * lc.prixUnitaire) as chiffreAffaire')
            ->join('l.ligneCommandes', 'lc')
            ->join('lc.commande', 'c')
            ->where('c.statut != :statut') // Exclure les commandes annulées si nécessaire
            ->setParameter('statut', 'ANNULEE')
            ->groupBy('l.id')
            ->orderBy('totalVendu', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
            
        return $results;
    }
}
