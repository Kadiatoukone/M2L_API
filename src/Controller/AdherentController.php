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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // ← AJOUT

#[Route('api/adherents', name: 'api_adherents_')]
class AdherentController extends AbstractController
{
    public function __construct(
        private AdherentRepository $adherentRepository,
        private EntityManagerInterface $entityManager,
        private ReservationsRepository $reservationsRepository,
        private UserPasswordHasherInterface $passwordHasher // ← AJOUT
    ) {
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\Adherent $user */
        $user = $this->getUser();
        
        return $this->json($user->toArray(), Response::HTTP_OK);
    }

    #[Route('/me', name: 'update_me', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateMe(Request $request): JsonResponse
    {
        /** @var \App\Entity\Adherent $adherent */
        $adherent = $this->getUser();
        
        $data = json_decode($request->getContent(), true);

        if (null === $data) {
            return $this->json(['message' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom'])) $adherent->setNom($data['nom']);
        if (isset($data['prenom'])) $adherent->setPrenom($data['prenom']);
        if (isset($data['ligue'])) $adherent->setLigue($data['ligue']);

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

        // Vérification des champs obligatoires
        foreach (['nom', 'prenom', 'email', 'mot_de_passe', 'ligue'] as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'message' => "Le champ '$field' est requis."
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $adherent = new Adherent();
        $adherent->setNom($data['nom']);
        $adherent->setPrenom($data['prenom']);
        $adherent->setEmail($data['email']);
        $adherent->setLigue($data['ligue']);
        $adherent->setRoles($data['roles'] ?? ['ROLE_USER']);

        // ✅ HASHAGE DU MOT DE PASSE — indispensable pour que login_check fonctionne
        $hashedPassword = $this->passwordHasher->hashPassword($adherent, $data['mot_de_passe']);
        $adherent->setMotDePasse($hashedPassword);

        $this->entityManager->persist($adherent);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Adhérent créé',
            'adherent' => $adherent->toArray()
        ], Response::HTTP_CREATED);
    }

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

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $adherent = $this->adherentRepository->find($id);

        if (!$adherent) {
            return $this->json(['message' => 'Adhérent introuvable'], Response::HTTP_NOT_FOUND);
        }

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
                'message' => 'Le corps de la requête est invalide (JSON malformé).'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['nom'])) $adherent->setNom($data['nom']);
        if (isset($data['prenom'])) $adherent->setPrenom($data['prenom']);
        if (isset($data['ligue'])) $adherent->setLigue($data['ligue']);

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
            return $this->json(['message' => 'Adhérent introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (count($this->reservationsRepository->findBy(['adherent' => $adherent])) > 0) {
            return $this->json([
                'message' => 'Cet adhérent ne peut pas être supprimé car il possède des réservations.'
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($adherent);
        $this->entityManager->flush();

        return $this->json(['message' => 'Adhérent supprimé'], Response::HTTP_OK);
    }

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