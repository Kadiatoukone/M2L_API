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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('api/adherents', name: 'api_adherents_')]
class AdherentController extends AbstractController
{
    public function __construct(
        private AdherentRepository $adherentRepository,
        private EntityManagerInterface $entityManager,
        private ReservationsRepository $reservationsRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    // ── Profil connecté ────────────────────────────────────────────────────────

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser()->toArray(), Response::HTTP_OK);
    }

    #[Route('/me', name: 'update_me', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateMe(Request $request): JsonResponse
    {
        /** @var Adherent $adherent */
        $adherent = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['message' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom']))    { $adherent->setNom($data['nom']); }
        if (isset($data['prenom'])) { $adherent->setPrenom($data['prenom']); }
        if (isset($data['ligue']))  { $adherent->setLigue($data['ligue']); }
        if (isset($data['poste']))  { $adherent->setPoste($data['poste']); }

        $this->entityManager->flush();

        return $this->json([
            'message'  => 'Profil mis à jour',
            'adherent' => $adherent->toArray(),
        ], Response::HTTP_OK);
    }

    #[Route('/me/password', name: 'change_password', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var Adherent $adherent */
        $adherent = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (empty($data['current_password']) || empty($data['new_password'])) {
            return $this->json([
                'message' => 'Champs obligatoires manquants (current_password, new_password)',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->passwordHasher->isPasswordValid($adherent, $data['current_password'])) {
            return $this->json([
                'message' => 'Mot de passe actuel incorrect',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (strlen($data['new_password']) < 6) {
            return $this->json([
                'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $adherent->setMotDePasse(
            $this->passwordHasher->hashPassword($adherent, $data['new_password'])
        );
        $this->entityManager->flush();

        return $this->json(['message' => 'Mot de passe mis à jour'], Response::HTTP_OK);
    }

    // ── Réservations du connecté ───────────────────────────────────────────────
    // NOTE : placée AVANT /{id}/reservations pour éviter les conflits de route

    #[Route('/me/reservations', name: 'my_reservations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myReservations(): JsonResponse
    {
        $adherent     = $this->getUser();
        $reservations = $this->reservationsRepository->findBy(['adherent' => $adherent]);

        return $this->json(
            array_map(fn (Reservations $r) => $r->toArray(), $reservations),
            Response::HTTP_OK
        );
    }

    // ── CRUD ───────────────────────────────────────────────────────────────────

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

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function get(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json(['message' => 'Adhérent introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($adherent->toArray(), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json(['message' => 'Adhérent introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var Adherent $currentUser */
        $currentUser = $this->getUser();

        if (
            !$this->isGranted('ROLE_ADMIN') &&
            $currentUser->getId() !== $adherent->getId()
        ) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json([
                'message' => 'Le corps de la requête est invalide (JSON malformé).',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom']))    { $adherent->setNom($data['nom']); }
        if (isset($data['prenom'])) { $adherent->setPrenom($data['prenom']); }
        if (isset($data['ligue']))  { $adherent->setLigue($data['ligue']); }
        if (isset($data['poste']))  { $adherent->setPoste($data['poste']); }

        $this->entityManager->flush();

        return $this->json([
            'message'  => 'Adhérent mis à jour',
            'adherent' => $adherent->toArray(),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json(['message' => 'Adhérent introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (count($this->reservationsRepository->findBy(['adherent' => $adherent])) > 0) {
            return $this->json([
                'message' => 'Cet adhérent ne peut pas être supprimé car il possède des réservations.',
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($adherent);
        $this->entityManager->flush();

        return $this->json(['message' => 'Adhérent supprimé'], Response::HTTP_OK);
    }

    #[Route('/{id}/reservations', name: 'reservations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function reservations(int $id): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json(['message' => 'Adhérent introuvable'], Response::HTTP_NOT_FOUND);
        }

        $reservations = $this->reservationsRepository->findBy(['adherent' => $adherent]);

        return $this->json(
            array_map(fn (Reservations $r) => $r->toArray(), $reservations),
            Response::HTTP_OK
        );
    }
}
