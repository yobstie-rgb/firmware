<?php

namespace App\Controller;

use App\Form\OtaUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class OtaController extends AbstractController
{
    #[Route('/admin/ota/upload/encoder', name: 'app_ota_upload', methods: ['GET', 'POST'])]
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

    #[Route('/api/ota/update/encode', name: 'app_api_ota_update', methods: ['GET'])]
    public function checkAndUpdate(Request $request): Response
    {
        $currentFirmwareVersion = $request->headers->get('x-esp32-version') ?? $request->query->get('version');
        $firmwareDir = $this->getParameter('firmware_directory');

        // Rechercher le fichier .bin le plus récent dans le dossier uploads
        $finder = new Finder();
        $finder->files()->in($firmwareDir)->name('*.bin')->sortByName();

        if (!$finder->hasResults()) {
            return new Response('Aucun fichier firmware disponible.', Response::HTTP_NOT_FOUND);
        }

        // Récupérer le dernier fichier
        $latestFile = null;
        foreach ($finder as $file) {
            $latestFile = $file;
        }

        $filePath = $latestFile->getRealPath();
        $fileName = $latestFile->getFilename();

        // Extrait la version si le nom suit le format firmware-v1.0.1-xxx.bin
        preg_match('/v(\d+\.\d+\.\d+)/', $fileName, $matches);
        $latestVersion = $matches[1] ?? '1.0.0';

        // Si l'ESP32 a déjà cette version ou une version supérieure
        if ($currentFirmwareVersion && version_compare($currentFirmwareVersion, $latestVersion, '>=')) {
            return new Response('Already up to date.', Response::HTTP_NOT_MODIFIED);
        }

        // Servir le fichier binaire à l'ESP32
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Length', (string) filesize($filePath));
        $response->headers->set('x-MD5', md5_file($filePath));

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        return $response;
    }

    #[Route('/ota/choice/operation', name:'app_ota_choice')]
    public function ota() : Response {

        return $this->render('ota/choice.html.twig', [
        ]);
    }
}
