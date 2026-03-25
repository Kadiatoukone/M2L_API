<?php

namespace App\Controller;

use App\Repository\SallesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class SallesController extends AbstractController
{
    #[Route('/mes-salles', name: 'mes_salles', methods: ['GET'])]
    public function getMesSalles(SallesRepository $sallesRepository): JsonResponse
    {
        $userConnecte = $this->getUser();

        // On cherche uniquement les salles gérées par un admin spécifique
        $mesSalles = $sallesRepository->findBy(['admin' => $userConnecte]);

        return $this->json($mesSalles, 200, [], ['groups' => 'salle:read']);
    }
}
