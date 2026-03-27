<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api', name: 'api_')]
class UploadController extends AbstractController
{
    #[Route('/upload', name: 'upload', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function upload(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['message' => 'Aucun fichier reçu'], 400);
        }

        // Vérification du type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(['message' => 'Format non supporté. Utilisez JPG, PNG ou WebP.'], 400);
        }

        // Taille max 5 Mo
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['message' => 'Fichier trop lourd (max 5 Mo).'], 400);
        }

        // Génération d'un nom unique pour éviter les collisions
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $slugger->slug($originalFilename);
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Déplacement dans public/uploads/salles/
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/salles';

        // Création du dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file->move($uploadDir, $newFilename);

        // On retourne l'URL publique accessible depuis le front
        return $this->json([
            'url'      => '/uploads/salles/' . $newFilename,
            'filename' => $newFilename,
        ], 200);
    }
}
