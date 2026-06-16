<?php

namespace App\Controller;

use App\Entity\Reservations;
use App\Repository\ReservationsRepository;
use App\Repository\SallesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/reservations', name: 'api_reservations_')]
class ReservationsController extends AbstractController
{
    private const STATUTS_VALIDES = ['EN_ATTENTE', 'VALIDEE', 'REFUSEE'];

    // Durée de conservation d'une réservation refusée avant suppression auto.
    private const JOURS_RETENTION_REFUSEE = 2;

    // Délai minimum avant la date de début (pas de réservation le jour même ni le lendemain).
    private const DELAI_MIN_JOURS = 2;

    // Statuts qui bloquent réellement un créneau (une demande en attente
    // "réserve" déjà le créneau jusqu'à ce qu'elle soit refusée).
    private const STATUTS_BLOQUANTS = ['EN_ATTENTE', 'VALIDEE'];

    public function __construct(
        private ReservationsRepository $reservationsRepository,
        private SallesRepository $sallesRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Request $request): JsonResponse
    {
        $this->nettoyerReservationsRefusees();

        $user = $this->getUser();
        $date = $request->query->get('date');
        $statut = $request->query->get('statut');

        $qb = $this->reservationsRepository->createQueryBuilder('r');

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            // L'admin voit tout.
        } elseif ($this->isGranted('ROLE_GESTIONNAIRE')) {
            // Un gestionnaire ne voit que les demandes pour les salles qu'il gère.
            $qb->join('r.salle', 's')
               ->andWhere('s.gestionnaire = :user')
               ->setParameter('user', $user);
        } else {
            // Un adhérent ne voit que ses propres réservations.
            $qb->andWhere('r.adherent = :user')->setParameter('user', $user);
        }

        if ($statut && in_array($statut, self::STATUTS_VALIDES, true)) {
            $qb->andWhere('r.statut = :statut')->setParameter('statut', $statut);
        }

        if ($date) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj) {
                $qb->andWhere('r.dateDebut <= :date AND r.dateFin >= :date')
                   ->setParameter('date', $dateObj);
            }
        }

        $reservations = $qb->getQuery()->getResult();

        return $this->json(array_map(fn (Reservations $r) => $r->toArray(), $reservations));
    }

    //CREER UNE RESERVATION
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            empty($data['dateDebut']) ||
            empty($data['dateFin']) ||
            empty($data['heureDebut']) ||
            empty($data['heureFin']) ||
            empty($data['salleId'])
        ) {
            return $this->json([
                'message' => 'Champs obligatoires manquants (dateDebut, dateFin, heureDebut, heureFin, salleId)'
            ], Response::HTTP_BAD_REQUEST);
        }

        $salle = $this->sallesRepository->find($data['salleId']);
        if (!$salle) {
            return $this->json(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
        }

        $typeResa = $data['typeResa'] ?? Reservations::TYPE_PONCTUEL;
        if (!in_array($typeResa, [Reservations::TYPE_PONCTUEL, Reservations::TYPE_MENSUEL], true)) {
            return $this->json([
                'message' => 'typeResa invalide. Valeurs acceptées : ponctuel, mensuel'
            ], Response::HTTP_BAD_REQUEST);
        }

        $dateDebut = new \DateTime($data['dateDebut']);
        $dateFin = new \DateTime($data['dateFin']);
        $heureDebut = new \DateTime($data['heureDebut']);
        $heureFin = new \DateTime($data['heureFin']);

        $premierJourPossible = new \DateTime(sprintf('+%d days', self::DELAI_MIN_JOURS));
        $premierJourPossible->setTime(0, 0);
        if ($dateDebut < $premierJourPossible) {
            return $this->json([
                'message' => sprintf(
                    'Impossible de réserver pour aujourd\'hui ou demain. La première date possible est le %s.',
                    $premierJourPossible->format('d/m/Y')
                )
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($this->creneauEnConflit($salle->getId(), $dateDebut, $dateFin, $heureDebut, $heureFin)) {
            return $this->json([
                'message' => 'Ce créneau est déjà réservé pour cette salle sur au moins une des dates choisies.'
            ], Response::HTTP_CONFLICT);
        }

        $reservation = new Reservations();
        $reservation->setDateDebut($dateDebut);
        $reservation->setDateFin($dateFin);
        $reservation->setHeureDebut($heureDebut);
        $reservation->setHeureFin($heureFin);
        $reservation->setMotif($data['motif'] ?? null);
        $reservation->setStatut('EN_ATTENTE');
        $reservation->setTypeResa($typeResa);
        $reservation->setSalle($salle);
        $reservation->setAdherent($this->getUser());

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Réservation créée',
            'reservation' => $reservation->toArray(),
        ], Response::HTTP_CREATED);
    }

    //VALIDER OU REFUSER UNE RESA (gestionnaire de la salle concernée, ou admin)
    #[Route('/{id}/statut', name: 'update_statut', methods: ['PATCH'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function updateStatut(int $id, Request $request): JsonResponse
    {
        $reservation = $this->reservationsRepository->find($id);

        if (!$reservation) {
            return $this->json(['message' => 'Réservation introuvable'], Response::HTTP_NOT_FOUND);
        }

        $estProprietaire = $reservation->getSalle()?->getGestionnaire() === $this->getUser();
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$estProprietaire) {
            return $this->json([
                'message' => 'Vous ne gérez pas la salle concernée par cette réservation'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['statut']) || !in_array($data['statut'], self::STATUTS_VALIDES, true)) {
            return $this->json(['message' => 'Statut invalide. Valeurs acceptées : EN_ATTENTE, VALIDEE, REFUSEE'], Response::HTTP_BAD_REQUEST);
        }

        $reservation->setStatut($data['statut']);
        $reservation->setRefusedAt($data['statut'] === 'REFUSEE' ? new \DateTime() : null);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Statut mis à jour',
            'statut' => $reservation->getStatut(),
        ]);
    }

    //SUPPRIMER UNE RESERVATION (adhérent propriétaire, gestionnaire de la salle, ou admin)
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id): JsonResponse
    {
        $reservation = $this->reservationsRepository->find($id);

        if (!$reservation) {
            return $this->json([
                'message' => 'Réservation introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        $estProprietaire = $reservation->getAdherent() === $this->getUser();
        $estGestionnaireSalle = $reservation->getSalle()?->getGestionnaire() === $this->getUser();

        if (
            !$this->isGranted('ROLE_SUPER_ADMIN') &&
            !$estProprietaire &&
            !$estGestionnaireSalle
        ) {
            return $this->json([
                'message' => 'Accès refusé'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($reservation);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Réservation supprimée'
        ]);
    }

    // Renvoie les créneaux déjà occupés (EN_ATTENTE ou VALIDEE) pour une salle
    // sur une période donnée — utilisé par l'appli pour griser les créneaux
    // indisponibles. Pas d'info sur qui a réservé ni pourquoi (vie privée).
    #[Route('/disponibilite/{salleId}', name: 'disponibilite', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function disponibilite(int $salleId, Request $request): JsonResponse
    {
        $salle = $this->sallesRepository->find($salleId);
        if (!$salle) {
            return $this->json(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin') ?? $dateDebut;

        if (!$dateDebut) {
            return $this->json(['message' => 'Paramètre dateDebut requis'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->reservationsRepository->createQueryBuilder('r')
            ->andWhere('r.salle = :salle')
            ->andWhere('r.statut IN (:statuts)')
            ->andWhere('r.dateDebut <= :fin AND r.dateFin >= :debut')
            ->setParameter('salle', $salle)
            ->setParameter('statuts', self::STATUTS_BLOQUANTS)
            ->setParameter('debut', new \DateTime($dateDebut))
            ->setParameter('fin', new \DateTime($dateFin));

        $reservations = $qb->getQuery()->getResult();

        return $this->json(array_map(fn (Reservations $r) => [
            'dateDebut' => $r->getDateDebut()->format('Y-m-d'),
            'dateFin' => $r->getDateFin()->format('Y-m-d'),
            'heureDebut' => $r->getHeureDebut()->format('H:i'),
            'heureFin' => $r->getHeureFin()->format('H:i'),
        ], $reservations));
    }

    // Vrai si [dateDebut,dateFin] × [heureDebut,heureFin] chevauche une
    // réservation existante (EN_ATTENTE ou VALIDEE) pour cette salle.
    private function creneauEnConflit(
        int $salleId,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin
    ): bool {
        $existantes = $this->reservationsRepository->createQueryBuilder('r')
            ->andWhere('r.salle = :salleId')
            ->andWhere('r.statut IN (:statuts)')
            ->andWhere('r.dateDebut <= :dateFin AND r.dateFin >= :dateDebut')
            ->setParameter('salleId', $salleId)
            ->setParameter('statuts', self::STATUTS_BLOQUANTS)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getResult();

        // Comparaison sur le format "H:i" : les DateTime d'heure n'ont pas
        // forcément la même date de référence (aujourd'hui pour une valeur
        // fraîchement créée, 1970-01-01 pour une valeur lue depuis la BDD),
        // donc comparer les objets DateTime directement serait faux.
        $debutStr = $heureDebut->format('H:i');
        $finStr = $heureFin->format('H:i');

        foreach ($existantes as $resa) {
            $resaDebutStr = $resa->getHeureDebut()->format('H:i');
            $resaFinStr = $resa->getHeureFin()->format('H:i');
            if ($debutStr < $resaFinStr && $finStr > $resaDebutStr) {
                return true;
            }
        }

        return false;
    }

    // Supprime les réservations refusées depuis plus de JOURS_RETENTION_REFUSEE jours.
    // Appelé à chaque listing (filet de sécurité simple, sans dépendre d'un vrai
    // cron système) — voir aussi la commande app:cleanup-reservations pour un
    // vrai déploiement avec tâche planifiée.
    private function nettoyerReservationsRefusees(): void
    {
        $seuil = new \DateTime(sprintf('-%d days', self::JOURS_RETENTION_REFUSEE));

        $anciennes = $this->reservationsRepository->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.refusedAt IS NOT NULL')
            ->andWhere('r.refusedAt < :seuil')
            ->setParameter('statut', 'REFUSEE')
            ->setParameter('seuil', $seuil)
            ->getQuery()
            ->getResult();

        if (empty($anciennes)) {
            return;
        }

        foreach ($anciennes as $resa) {
            $this->entityManager->remove($resa);
        }
        $this->entityManager->flush();
    }
}
