<?php

namespace App\Repository;

use App\Entity\TypeSalle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeSalle>
 */
class TypeSalleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeSalle::class);
    }

    // Récupère tous les types groupés par catégorie (sport / evenement)
    public function findAllGroupedByCategorie(): array
    {
        $results = $this->createQueryBuilder('t')
            ->orderBy('t.categorie', 'ASC')
            ->addOrderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = ['sport' => [], 'evenement' => []];

        foreach ($results as $type) {
            $grouped[$type->getCategorie()][] = $type;
        }

        return $grouped;
    }

    // Cherche un type par libellé exact (insensible à la casse) pour éviter les doublons
    public function findByLibelle(string $libelle): ?TypeSalle
    {
        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.libelle) = LOWER(:libelle)')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }
}