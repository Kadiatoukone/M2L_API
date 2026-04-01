<?php

namespace App\Repository;

use App\Entity\Adherent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection; 
/**
 * @extends ServiceEntityRepository<Adherent>
 */
class AdherentRepository extends ServiceEntityRepository
{
    private Connection $connection;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adherent::class);
        $this->connection = $registry->getConnection(); 
    }

    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.nom LIKE :val OR a.prenom LIKE :val')
            ->setParameter('val', '%'.$name.'%')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmail(string $email): ?Adherent
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findAllRaw(): array
    {
        $sql = "SELECT id, nom, prenom, email FROM adherent";
        $result = $this->connection->executeQuery($sql);
        return $result->fetchAllAssociative();
    }

    public function insertRaw(string $nom, string $prenom, string $email): int
    {
        $sql = "INSERT INTO adherent (nom, prenom, email) VALUES (:nom, :prenom, :email)";

        $this->connection->executeStatement($sql, [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateRaw(int $id, string $email): int
    {
        $sql = "UPDATE adherent SET email = :email WHERE id = :id";
        
        return $this->connection->executeStatement($sql, [
            'id' => $id,
            'email' => $email,
        ]);
    }

    public function deleteRaw(int $id): int
    {
        $sql = "DELETE FROM adherent WHERE id = :id";
        return $this->connection->executeStatement($sql, ['id' => $id]);
    }
}
//    /**
//     * @return Adherent[] Returns an array of Adherent objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Adhérent
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

