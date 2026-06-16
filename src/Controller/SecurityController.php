<?php

namespace App\Controller;

use App\Entity\Adherent;
use App\Repository\AdherentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private AdherentRepository $adherentRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            empty($data['email']) ||
            empty($data['password']) ||
            empty($data['nom']) ||
            empty($data['prenom']) ||
            empty($data['ligue']) ||
            empty($data['poste']) ||
            empty($data['numero_adherent'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Champs obligatoires manquants (email, password, nom, prenom, ligue, poste, numero_adherent)',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($this->adherentRepository->findOneBy(['email' => $data['email']])) {
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé',
            ], Response::HTTP_CONFLICT);
        }

        if ($this->adherentRepository->findOneBy(['numero_adherent' => $data['numero_adherent']])) {
            return $this->json([
                'success' => false,
                'message' => 'Ce numéro d\'adhérent est déjà utilisé',
            ], Response::HTTP_CONFLICT);
        }

        $adherent = new Adherent();
        $adherent->setEmail($data['email']);
        $adherent->setNom($data['nom']);
        $adherent->setPrenom($data['prenom']);
        $adherent->setLigue($data['ligue']);
        $adherent->setPoste($data['poste']);
        $adherent->setNumeroAdherent($data['numero_adherent']);
        $adherent->setMotDePasse(
            $this->passwordHasher->hashPassword($adherent, $data['password'])
        );

        $this->entityManager->persist($adherent);
        $this->entityManager->flush();

        return $this->json([
            'success'  => true,
            'message'  => 'Adhérent créé avec succès',
            'adherent' => $adherent->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json([
            'message' => 'Authentification gérée par JWT',
        ]);
    }
}
