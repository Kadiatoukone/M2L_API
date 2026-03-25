<?php

namespace App\Controller;

use App\Entity\Adherent;
use App\Entity\Reservations;
use App\Repository\ReservationsRepository;
use App\Repository\AdherentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('api/adherents', name: 'api_adherents_')]
class AdherentController extends AbstractController
{
    public function __construct(
        private AdherentRepository $adherentRepository,
        private EntityManagerInterface $entityManager,
        private ReservationsRepository $reservationsRepository
    ) {
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        return $this->json($user->toArray(), Response::HTTP_OK);
    }

    // NOTE: Modifier MON profil (utilise le token, pas d'ID dans l'URL)
    #[Route('/me', name: 'update_me', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateMe(Request $request): JsonResponse
    {
        // $this->getUser() renvoie l'utilisateur connecté grâce au token
        $adherent = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['message' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        // On met à jour les infos (pas besoin de vérifier l'ID, c'est forcément lui)
        if (isset($data['nom'])) {
            $adherent->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $adherent->setPrenom($data['prenom']);
        }
        if (isset($data['ligue'])) {
            $adherent->setLigue($data['ligue']);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Profil mis à jour',
            'adherent' => $adherent->toArray()
        ], Response::HTTP_OK);
    }

    // Create 
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json([
                'message' => 'Le corps de la requête est invalide (JSON malformé).'
            ], Response::HTTP_BAD_REQUEST);
        }
        $adherent = new Adherent();
        $adherent->setNom($data['nom']);
        $adherent->setPrenom($data['prenom']);
        $adherent->setLigue($data['ligue']);
        $adherent->setRoles($data['roles']);

        $this->entityManager->persist($adherent);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Adhérent créé',
            'adherent' => $adherent->toArray()
        ], Response::HTTP_CREATED);
    }

    // read all 
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        $adherents = $this->adherentRepository->findAll();

        return $this->json(
            array_map(fn (Adherent $a) => $a->toArray(), $adherents),
            Response::HTTP_OK
        );
    }

    // read one
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function get(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json([
                'message' => 'Adhérent introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($adherent->toArray(), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(
        int $id,
        Request $request
    ): JsonResponse {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json([
                'message' => 'Adhérent introuvable'
            ], Response::HTTP_NOT_FOUND);
        }


        $currentUser = $this->getUser();

        // Sécurité : seul l'admin ou l'utilisateur lui-même
        if (
            !$this->isGranted('ROLE_ADMIN') &&
            $currentUser->getId() !== $adherent->getId()
        ) {
            return $this->json([
                'message' => 'Accès refusé'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json([
                'message' => 'Le corps de la requête est invalide (JSON malformé).'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom'])) {
            $adherent->setNom($data['nom']);
        }

        if (isset($data['prenom'])) {
            $adherent->setPrenom($data['prenom']);
        }

        if (isset($data['ligue'])) {
            $adherent->setLigue($data['ligue']);
        }

        if ($this->isGranted('ROLE_ADMIN') && isset($data['roles'])) {
            $adherent->setRoles($data['roles']);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Adhérent mis à jour',
            'adherent' => $adherent->toArray()
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json([
                'message' => 'Adhérent introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        // On ne peut pas supprimer un adhérent qui a des réservations
        if (count($this->reservationsRepository->findBy(['adherent' => $adherent])) > 0) {
            return $this->json([
                'message' => 'Cet adhérent ne peut pas être supprimé car il possède des réservations.'
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($adherent);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Adhérent supprimé'
        ], Response::HTTP_OK);
    }

    // NOTE: Voir MES réservations (plus besoin de passer l'ID)
    // IMPORTANT : Je place cette route AVANT la route /{id}/reservations pour éviter les conflits
    #[Route('/me/reservations', name: 'my_reservations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myReservations(): JsonResponse
    {
        $adherent = $this->getUser();
        $reservations = $this->reservationsRepository->findBy(['adherent' => $adherent]);

        return $this->json(
            array_map(fn (Reservations $r) => $r->toArray(), $reservations),
            Response::HTTP_OK
        );
    }

    // Read reservations of an adherent
    #[Route('/{id}/reservations', name: 'reservations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function reservations(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json([
                'message' => 'Adhérent introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        $reservations = $this->reservationsRepository->findBy(['adherent' => $adherent]);

        return $this->json(
            array_map(fn (Reservations $r) => $r->toArray(), $reservations),
            Response::HTTP_OK
        );
    }
}