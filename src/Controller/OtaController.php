<?php

namespace App\Controller;

use App\Form\OtaUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class OtaController extends AbstractController
{
    #[Route('/admin/ota/upload', name: 'app_ota_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(OtaUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firmwareFile = $form->get('firmwareFile')->getData();
            $version = $form->get('version')->getData();

            if ($firmwareFile) {
                $originalFilename = pathinfo($firmwareFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                // Nom unique avec la version : firmware-v1.0.2-65a8d9f.bin
                // $newFilename = sprintf('firmware-v%s-%s.%s', $version, uniqid(), $firmwareFile->guessExtension() ?? 'bin');
                $newFilename = sprintf('firmware-v%s-%s.bin', $version, uniqid());
                try {
                    $firmwareFile->move(
                        $this->getParameter('firmware_directory'),
                        $newFilename
                    );

                    $this->addFlash('success', 'Le fichier OTA a été téléversé avec succès : ' . $newFilename);

                    // Redirection vers la liste ou le formulaire
                    return $this->redirectToRoute('app_ota_upload');

                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'enregistrement du fichier.');
                }
            }
        }

        return $this->render('ota/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/api/ota/update', name: 'app_api_ota_update', methods: ['GET'])]
    public function checkAndUpdate(Request $request): Response
    {
        // 1. Récupérer la version actuelle transmise par l'ESP32
        // ESP32httpUpdate / HTTPClient envoie souvent la version dans un header personnalisé ou Query parameter
        $currentFirmwareVersion = $request->headers->get('x-esp32-version') ?? $request->query->get('version');

        // 2. Définir la dernière version disponible sur le serveur
        // (Idéalement stockée en Base de Données ou récupérée dynamiquement depuis le dossier des uploads)
        $latestVersion = '1.0.1';
        $firmwareFileName = 'firmware-v1.0.1.bin'; // Ajustez selon votre nom de fichier réel
        $firmwarePath = $this->getParameter('firmware_directory') . '/' . $firmwareFileName;

        // Vérifier que le fichier binaire existe physiquement sur le serveur
        if (!file_exists($firmwarePath)) {
            return new Response('Firmware file not found on server.', Response::HTTP_NOT_FOUND);
        }

        // 3. Comparer la version de l'ESP32 avec la dernière version disponible
        if ($currentFirmwareVersion && version_compare($currentFirmwareVersion, $latestVersion, '>=')) {
            // L'ESP32 est déjà à jour -> HTTP 304 Not Modified
            return new Response('Already up to date.', Response::HTTP_NOT_MODIFIED);
        }

        // 4. Préparer la réponse binaire pour le téléchargement OTA
        $response = new BinaryFileResponse($firmwarePath);

        // En-têtes HTTP essentiels pour la bibliothèque ESP32httpUpdate / HTTPClient
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', (string) filesize($firmwarePath));
        $response->headers->set('x-MD5', md5_file($firmwarePath)); // Optionnel mais recommandé pour vérifier l'intégrité

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $firmwareFileName
        );

        return $response;
    }
}
