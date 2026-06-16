<?php

namespace App\Repository;

use App\Entity\Adherent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Adherent>
 */
class AdherentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adherent::class);
    }

    public function findOneByEmail(string $email): ?Adherent
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findOneByNumeroAdherent(string $numero): ?Adherent
    {
        return $this->findOneBy(['numero_adherent' => $numero]);
    }

    /** @return Adherent[] */
    public function findByLigue(string $ligue): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.ligue = :ligue')
            ->setParameter('ligue', $ligue)
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Adherent[] */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.nom LIKE :val OR a.prenom LIKE :val')
            ->setParameter('val', '%' . $name . '%')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, int> Nombre d'adhérents par ligue */
    public function countByLigue(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.ligue, COUNT(a.id_adherent) as total')
            ->groupBy('a.ligue')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['ligue']] = (int) $row['total'];
        }

        return $result;
    }
}
