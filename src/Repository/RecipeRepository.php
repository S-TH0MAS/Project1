<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    //    /**
    //     * @return Recipe[] Returns an array of Recipe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Recipe
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return Recipe[] Returns an array of Recipe objects with authors preloaded
     */
    public function findRecipesWithAuthors(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            // On joint l'auteur ('a') à la recette
            ->leftJoin('r.author', 'a')
            // CRUCIAL : On sélectionne les données de 'a' pour les mettre en mémoire tout de suite
            ->addSelect('a')
            ->orderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les recettes créées par un auteur spécifique (avec pagination)
     * 
     * @return Recipe[] Returns an array of Recipe objects with authors preloaded
     */
    public function findRecipesByAuthor(Client $author, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.author', 'a')
            ->addSelect('a') // On charge les infos de l'auteur
            ->where('r.author = :author')
            ->setParameter('author', $author)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les recettes favorites d'un utilisateur (avec pagination)
     * 
     * @return Recipe[] Returns an array of Recipe objects with authors preloaded
     */
    public function findFavoriteRecipes(Client $user, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('r')
            // 1. On charge l'auteur de la recette (pour l'affichage)
            ->leftJoin('r.author', 'a')
            ->addSelect('a')
            
            // 2. On fait une jointure pour filtrer sur les favoris de l'utilisateur
            ->innerJoin('r.clients', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
            
            ->orderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
