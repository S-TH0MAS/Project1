<?php

namespace App\Repository;

use App\Entity\ClientItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClientItem>
 *
 * @method ClientItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientItem[]    findAll()
 * @method ClientItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientItem::class);
    }
}