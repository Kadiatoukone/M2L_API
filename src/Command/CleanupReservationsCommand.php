<?php

namespace App\Command;

use App\Repository\ReservationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// À planifier en production (cron / tâche planifiée Windows), ex. une fois par jour :
//   php bin/console app:cleanup-reservations
// Le contrôleur effectue déjà ce nettoyage à la volée à chaque listing, cette
// commande est un filet de sécurité supplémentaire (utile si l'API n'est pas
// consultée pendant plusieurs jours).
#[AsCommand(
    name: 'app:cleanup-reservations',
    description: 'Supprime les réservations refusées depuis plus de 2 jours.',
)]
class CleanupReservationsCommand extends Command
{
    private const JOURS_RETENTION_REFUSEE = 2;

    public function __construct(
        private ReservationsRepository $reservationsRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $seuil = new \DateTime(sprintf('-%d days', self::JOURS_RETENTION_REFUSEE));

        $anciennes = $this->reservationsRepository->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.refusedAt IS NOT NULL')
            ->andWhere('r.refusedAt < :seuil')
            ->setParameter('statut', 'REFUSEE')
            ->setParameter('seuil', $seuil)
            ->getQuery()
            ->getResult();

        foreach ($anciennes as $resa) {
            $this->entityManager->remove($resa);
        }
        $this->entityManager->flush();

        $io->success(sprintf('%d réservation(s) refusée(s) supprimée(s).', count($anciennes)));

        return Command::SUCCESS;
    }
}
