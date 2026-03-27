<?php

namespace App\Controller;

use App\Entity\TypeSalle;
use App\Repository\TypeSalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class TypeSalleController extends AbstractController
{
    // Récupère tous les types groupés par catégorie
    #[Route('/types-salles', name: 'type_salle_list', methods: ['GET'])]
    public function list(TypeSalleRepository $repo): JsonResponse
    {
        $grouped = $repo->findAllGroupedByCategorie();

        // On sérialise manuellement pour renvoyer un format propre au front
        $data = [
            'sport'     => array_map(fn($t) => ['id' => $t->getId(), 'libelle' => $t->getLibelle()], $grouped['sport']),
            'evenement' => array_map(fn($t) => ['id' => $t->getId(), 'libelle' => $t->getLibelle()], $grouped['evenement']),
        ];

        return $this->json($data, 200);
    }

    // Crée un nouveau type s'il n'existe pas déjà (sinon retourne l'existant)
    #[Route('/types-salles', name: 'type_salle_create', methods: ['POST'])]
    public function create(
        Request $request,
        TypeSalleRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['libelle']) || empty($data['categorie'])) {
            return $this->json(['message' => 'Le libellé et la catégorie sont obligatoires'], 400);
        }

        $categorie = strtolower($data['categorie']);
        if (!in_array($categorie, [TypeSalle::CATEGORIE_SPORT, TypeSalle::CATEGORIE_EVENEMENT])) {
            return $this->json(['message' => 'Catégorie invalide. Valeurs acceptées : sport, evenement'], 400);
        }

        // On vérifie si le libellé existe déjà pour éviter les doublons
        $existant = $repo->findByLibelle($data['libelle']);
        if ($existant) {
            return $this->json([
                'id'       => $existant->getId(),
                'libelle'  => $existant->getLibelle(),
                'categorie'=> $existant->getCategorie(),
            ], 200);
        }

        $typeSalle = new TypeSalle();
        $typeSalle->setLibelle(ucfirst(strtolower($data['libelle'])));
        $typeSalle->setCategorie($categorie);

        $em->persist($typeSalle);
        $em->flush();

        return $this->json([
            'id'        => $typeSalle->getId(),
            'libelle'   => $typeSalle->getLibelle(),
            'categorie' => $typeSalle->getCategorie(),
        ], 201);
    }
}