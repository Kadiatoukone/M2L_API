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
    public function __construct(
        private ReservationsRepository $reservationsRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $date = $request->query->get('date'); 

        $qb = $this->reservationsRepository->createQueryBuilder('r');

        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            $qb->andWhere('r.adherent = :user')->setParameter('user', $user);
        }

        if ($date) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj) {
                $qb->andWhere('r.dateDebut <= :date AND r.dateFin >= :date')
                    ->setParameter('date', $dateObj);
            }
        }

        $reservations = $qb->getQuery()->getResult();

        return $this->json(array_map(
    fn (Reservations $r) => [
        'id'         => $r->getId(),
        'dateDebut'  => $r->getDateDebut()->format('Y-m-d'),
        'dateFin'    => $r->getDateFin()->format('Y-m-d'),
        'heureDebut' => $r->getHeureDebut()->format('H:i'),
        'heureFin'   => $r->getHeureFin()->format('H:i'),
        'motif'      => $r->getMotif(),
        'statut'     => $r->getStatut(),
        'salle'      => $r->getSalle() ? [   
            'id'    => $r->getSalle()->getId(),
            'nom'   => $r->getSalle()->getNom(),
            'ville' => $r->getSalle()->getVille(),
        ] : null,
        'adherent' => $r->getAdherent() ? [
            'id'     => $r->getAdherent()->getId(),
            'nom'    => $r->getAdherent()->getNom(),
            'prenom' => $r->getAdherent()->getPrenom(),
            'email'  => $r->getAdherent()->getEmail(),
        ] : null,
    ],
    $reservations
));
    }

    //CREER UNE RESERVATION
    #[Route('', name: 'create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function create(Request $request, SallesRepository $sallesRepository): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (empty($data['dateDebut']) || empty($data['dateFin']) ||
        empty($data['heureDebut']) || empty($data['heureFin']) || empty($data['salleId'])) {
        return $this->json(['message' => 'Champs obligatoires manquants'], Response::HTTP_BAD_REQUEST);
    }

    $salle = $sallesRepository->find($data['salleId']);
    if (!$salle) {
        return $this->json(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
    }

    $reservation = new Reservations();
    $reservation->setDateDebut(new \DateTime($data['dateDebut']));
    $reservation->setDateFin(new \DateTime($data['dateFin']));
    $reservation->setHeureDebut(new \DateTime($data['heureDebut']));
    $reservation->setHeureFin(new \DateTime($data['heureFin']));
    $reservation->setMotif($data['motif'] ?? null);
    $reservation->setStatut('EN_ATTENTE');
    $reservation->setAdherent($this->getUser());
    $reservation->setSalle($salle); 

    $this->entityManager->persist($reservation);
    $this->entityManager->flush();

    return $this->json(['message' => 'Réservation créée'], Response::HTTP_CREATED);
}

    //VALISER OU REFUSER UNE RESA (gestionnaires uniquement)
    #[Route('/{id}/statut', name: 'update_statut', methods: ['PATCH'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function updateStatut(int $id, Request $request): JsonResponse
    {
        $reservation = $this->reservationsRepository->find($id);

        if (!$reservation) {
            return $this->json(['message' => 'Réservation introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $statutsValides = ['EN_ATTENTE', 'VALIDEE', 'REFUSEE'];
        if (empty($data['statut']) || !in_array($data['statut'], $statutsValides)) {
            return $this->json(['message' => 'Statut invalide. Valeurs acceptées : EN_ATTENTE, VALIDEE, REFUSEE'], Response::HTTP_BAD_REQUEST);
        }

        $reservation->setStatut($data['statut']);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Statut mis à jour',
            'statut' => $reservation->getStatut(),
        ]);
    }

    //SUPPRIMER UNE RESERVATION (adhérent)
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $reservation = $this->reservationsRepository->find($id);

        if (!$reservation) {
            return $this->json([
                'message' => 'Réservation introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        if (
            !$this->isGranted('ROLE_ADMIN') &&
            $reservation->getAdherent() !== $this->getUser()
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
}