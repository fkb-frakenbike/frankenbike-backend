<?php

namespace App\Repository;

use App\Entity\ComponentCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComponentCategory>
 */
class ComponentCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComponentCategory::class);
    }

    // Add custom query methods here if needed.
}