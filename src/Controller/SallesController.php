<?php

namespace App\Controller;

use App\Entity\Horaire;
use App\Entity\Salles;
use App\Repository\SallesRepository;
use App\Repository\TypeSalleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
class SallesController extends AbstractController
{
    // GET toutes les salles
    #[Route('/salles', name: 'salles_list', methods: ['GET'])]
public function getSalles(Request $request, SallesRepository $sallesRepository): JsonResponse
{
    $categorie = $request->query->get('categorie');
    $libelle   = $request->query->get('libelle'); 

    $qb = $sallesRepository->createQueryBuilder('s')
        ->join('s.typeSalle', 't');

    if ($categorie) {
        $qb->andWhere('t.categorie = :categorie')
            ->setParameter('categorie', $categorie);
    }

    if ($libelle) {
        $qb->andWhere('LOWER(t.libelle) = LOWER(:libelle)') 
            ->setParameter('libelle', $libelle);
    }

    $salles = $qb->getQuery()->getResult();

    return $this->json(array_map(fn(Salles $s) => $s->toArray(), $salles));
}

    // GET toutes les salles du gestionnaire connecté
    #[Route('/mes-salles', name: 'mes_salles', methods: ['GET'])]
    public function getMesSalles(SallesRepository $sallesRepository): JsonResponse
    {
        $gestionnaire = $this->getUser();
        $mesSalles = $sallesRepository->findBy(['gestionnaire' => $gestionnaire]);

        return $this->json(array_map(fn(Salles $s) => $s->toArray(), $mesSalles));
    }

    // GET fiche descriptive d'une salle
    #[Route('/salles/{id}', name: 'salle_show', methods: ['GET'])]
    public function getSalle(int $id, SallesRepository $sallesRepository): JsonResponse
    {
        $salle = $sallesRepository->find($id);

        if (!$salle) {
            return $this->json(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($salle->toArray());
    }

    // POST créer une salle
    #[Route('/salles', name: 'salle_create', methods: ['POST'])]
    public function createSalle(Request $request, EntityManagerInterface $entityManager, TypeSalleRepository $typeSalleRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['nom']) || empty($data['adresse']) || empty($data['ville']) || empty($data['capacite']) || empty($data['typeSalle'])) {
            return $this->json(['message' => 'Champs obligatoires manquants (nom, adresse, ville, capacite, typeSalle)'], Response::HTTP_BAD_REQUEST);
        }

        $typeSalleData = $data['typeSalle'];
        if (empty($typeSalleData['libelle']) || empty($typeSalleData['categorie'])) {
            return $this->json(['message' => 'typeSalle doit contenir libelle et categorie'], Response::HTTP_BAD_REQUEST);
        }

        $typeSalle = $typeSalleRepository->findByLibelle($typeSalleData['libelle']);
        if (!$typeSalle) {
            $typeSalle = new \App\Entity\TypeSalle();
            $typeSalle->setLibelle($typeSalleData['libelle']);
            $typeSalle->setCategorie($typeSalleData['categorie']);
            $entityManager->persist($typeSalle);
        }

        $salle = new Salles();
        $salle->setNom($data['nom']);
        $salle->setAdresse($data['adresse']);
        $salle->setVille($data['ville']);
        $salle->setCapacite((int) $data['capacite']);
        $salle->setTypeSalle($typeSalle);
        $salle->setDescription($data['description'] ?? null);
        $salle->setPhoto($data['photo'] ?? null);
        $salle->setGestionnaire($this->getUser());

        if (!empty($data['horaires']) && is_array($data['horaires'])) {
            foreach ($data['horaires'] as $horaireData) {
                if (empty($horaireData['jour']) || empty($horaireData['heureOuverture']) || empty($horaireData['heureFermeture'])) {
                    continue;
                }
                $horaire = new Horaire();
                $horaire->setJour($horaireData['jour']);
                $horaire->setHeureOuverture(new \DateTime($horaireData['heureOuverture']));
                $horaire->setHeureFermeture(new \DateTime($horaireData['heureFermeture']));
                $horaire->setStatut($horaireData['statut'] ?? 'ouvert');
                $salle->addHoraire($horaire);
            }
        }

        $entityManager->persist($salle);
        $entityManager->flush();

        return $this->json($salle->toArray(), Response::HTTP_CREATED);
    }

    // PUT modifier une salle (gestionnaire propriétaire ou super_admin)
    #[Route('/salles/{id}', name: 'salle_update', methods: ['PUT'])]
    public function updateSalle(int $id, Request $request, SallesRepository $sallesRepository, EntityManagerInterface $entityManager, TypeSalleRepository $typeSalleRepository): JsonResponse
    {
        $salle = $sallesRepository->find($id);

        if (!$salle) {
            return $this->json(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $salle->getGestionnaire() !== $this->getUser()) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) $salle->setNom($data['nom']);
        if (isset($data['adresse'])) $salle->setAdresse($data['adresse']);
        if (isset($data['ville'])) $salle->setVille($data['ville']);
        if (isset($data['capacite'])) $salle->setCapacite((int) $data['capacite']);
        if (array_key_exists('description', $data)) $salle->setDescription($data['description']);
        if (array_key_exists('photo', $data)) $salle->setPhoto($data['photo']);

        if (!empty($data['typeSalle'])) {
            $typeSalleData = $data['typeSalle'];
            if (!empty($typeSalleData['libelle']) && !empty($typeSalleData['categorie'])) {
                $typeSalle = $typeSalleRepository->findByLibelle($typeSalleData['libelle']);
                if (!$typeSalle) {
                    $typeSalle = new \App\Entity\TypeSalle();
                    $typeSalle->setLibelle($typeSalleData['libelle']);
                    $typeSalle->setCategorie($typeSalleData['categorie']);
                    $entityManager->persist($typeSalle);
                }
                $salle->setTypeSalle($typeSalle);
            }
        }

        // Mise à jour des horaires : on supprime les anciens et on recrée
        if (isset($data['horaires']) && is_array($data['horaires'])) {
            foreach ($salle->getHoraires() as $horaire) {
                $salle->removeHoraire($horaire);
            }
            foreach ($data['horaires'] as $horaireData) {
                if (empty($horaireData['jour']) || empty($horaireData['heureOuverture']) || empty($horaireData['heureFermeture'])) {
                    continue;
                }
                $horaire = new Horaire();
                $horaire->setJour($horaireData['jour']);
                $horaire->setHeureOuverture(new \DateTime($horaireData['heureOuverture']));
                $horaire->setHeureFermeture(new \DateTime($horaireData['heureFermeture']));
                $horaire->setStatut($horaireData['statut'] ?? 'ouvert');
                $salle->addHoraire($horaire);
            }
        }

        $entityManager->flush();

        return $this->json($salle->toArray());
    }

    // PATCH mettre à jour le statut d'un horaire (ouvert/fermé)
    #[Route('/horaires/{id}/statut', name: 'horaire_statut', methods: ['PATCH'])]
    public function updateHoraireStatut(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $horaire = $entityManager->getRepository(Horaire::class)->find($id);

        if (!$horaire) {
            return $this->json(['message' => 'Horaire introuvable'], Response::HTTP_NOT_FOUND);
        }

        $salle = $horaire->getSalle();
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $salle->getGestionnaire() !== $this->getUser()) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['statut']) || !in_array($data['statut'], ['ouvert', 'ferme'])) {
            return $this->json(['message' => 'Statut invalide. Valeurs acceptées : ouvert, ferme'], Response::HTTP_BAD_REQUEST);
        }

        $horaire->setStatut($data['statut']);
        $entityManager->flush();

        return $this->json(['message' => 'Statut mis à jour', 'statut' => $horaire->getStatut()]);
    }

    // DELETE supprimer une salle (SUPER_ADMIN uniquement)
    #[Route('/salles/{id}', name: 'salle_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function deleteSalle(int $id, SallesRepository $sallesRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $salle = $sallesRepository->find($id);

        if (!$salle) {
            return $this->json(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($salle);
        $entityManager->flush();

        return $this->json(['message' => 'Salle supprimée avec succès']);
    }
}
