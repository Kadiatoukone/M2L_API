<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route; 
use App\Entity\Gestionnaires;
use App\Repository\GestionnairesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')] 
class GestionnairesController extends AbstractController
{
    #[Route('/me', name: 'me', methods: ['GET'])] 
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return $this->json($user, 200, [], ['groups' => 'admin:read']);
    }

    //CREATION DES GESTIONNAIRES
    #[Route('/gestionnaires', name: 'create_gestionnaire', methods: ['POST'])]
    public function createGestionnaire(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        // On vérifie que les champs existent bien
        if (!isset($data['identifiant']) || !isset($data['password'])) {
            return $this->json(['message' => 'Identifiant et mot de passe obligatoires'], 400);
        }

        // On crée le nouvel utilisateur
        $nouveauGestionnaire = new Gestionnaires();
        $nouveauGestionnaire->setIdentifiant($data['identifiant']);
        $nouveauGestionnaire->setRoles(['ROLE_GESTIONNAIRE']); // Rôle par défaut

        $nouveauGestionnaire->setNom($data['nom']); 
        
        $nouveauGestionnaire->setPrenom($data['prenom']);

        $nouveauGestionnaire->setEmail($data['email']);
        
        // On met temporairement le mot de passe EN CLAIR pour le Validator
        $nouveauGestionnaire->setPassword($data['password']);

        // On vérifie les règles de la CNIL
        $erreurs = $validator->validate($nouveauGestionnaire);
        
        if (count($erreurs) > 0) {
            $messagesErreurs = [];
            foreach ($erreurs as $erreur) {
                $messagesErreurs[] = $erreur->getMessage();
            }
            // Si le mot de passe est trop faible, on bloque et on renvoie l'erreur CNIL (Erreur 400)
            return $this->json(['erreurs' => $messagesErreurs], 400);
        }

        // Si c'est validé, on HACHE le mot de passe pour la sécurité
        $motDePasseHache = $passwordHasher->hashPassword(
            $nouveauGestionnaire,
            $data['password']
        );
        $nouveauGestionnaire->setPassword($motDePasseHache);

        // On sauvegarde dans la base de données
        $entityManager->persist($nouveauGestionnaire);
        $entityManager->flush();

        // On renvoie les infos 
        return $this->json($nouveauGestionnaire, 201, [], ['groups' => 'admin:read']);
    }

    //MODIFICATION DES GESTIONNAIRES
    #[Route('/gestionnaires/{id}', name: 'update_gestionnaire', methods: ['PUT'])]
    public function updateGestionnaire(
        int $id,
        Request $request,
        GestionnairesRepository $gestionnairesRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $gestionnaire = $gestionnairesRepository->find($id);

        if (!$gestionnaire) {
            return $this->json(['message' => 'Gestionnaire introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $gestionnaire->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $gestionnaire->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $gestionnaire->setEmail($data['email']);
        }
        if (isset($data['identifiant'])) {
            $gestionnaire->setIdentifiant($data['identifiant']);
        }
        if (isset($data['roles'])) {
            $gestionnaire->setRoles($data['roles']);
        }
        if (isset($data['password'])) {
            $gestionnaire->setPassword($data['password']);
            $erreurs = $validator->validate($gestionnaire);
            if (count($erreurs) > 0) {
                $messagesErreurs = [];
                foreach ($erreurs as $erreur) {
                    $messagesErreurs[] = $erreur->getMessage();
                }
                return $this->json(['erreurs' => $messagesErreurs], 400);
            }
            $gestionnaire->setPassword($passwordHasher->hashPassword($gestionnaire, $data['password']));
        }

        $entityManager->flush();

        return $this->json($gestionnaire, 200, [], ['groups' => 'admin:read']);
    }

    //SUPPRESSION DES GESTIONNAIRES
    #[Route('/gestionnaires/{id}', name: 'delete_gestionnaire', methods: ['DELETE'])]
    public function deleteGestionnaire(
        int $id, 
        GestionnairesRepository $gestionnairesRepository, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // On cherche le gestionnaire dans la base de données grâce à son ID
        $gestionnaire = $gestionnairesRepository->find($id);

        // S'il n'existe pas, on renvoie une erreur 404
        if (!$gestionnaire) {
            return $this->json(['message' => 'Gestionnaire introuvable'], 404);
        }

        // On empêche l'utilisateur connecté de se supprimer lui-même
        if ($this->getUser() === $gestionnaire) {
            return $this->json(['message' => 'Action refusée : vous ne pouvez pas supprimer votre propre compte.'], 403);
        }

        // On demande à Doctrine de le supprimer
        $entityManager->remove($gestionnaire);
        $entityManager->flush();

        // On confirme que tout va bien
        return $this->json(['message' => 'Le gestionnaire a été supprimé avec succès.'], 200);
    }

    //LISTAGE DES GESTIONNAIRES
    #[Route('/gestionnaires', name: 'list_gestionnaires', methods: ['GET'])]
    public function getGestionnaires(GestionnairesRepository $gestionnairesRepository): JsonResponse
    {
        // On récupère TOUS les gestionnaires de la table
        $gestionnaires = $gestionnairesRepository->findAll();

        // On les renvoie en JSON  + le groupe 'admin:read' garantit que les mots de passe ne seront pas envoyés 
        return $this->json($gestionnaires, 200, [], ['groups' => 'admin:read']);
    }


}