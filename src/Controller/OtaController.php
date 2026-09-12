<?php

namespace App\Controller;

use App\Form\OtaUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                $newFilename = sprintf('firmware-v%s-%s.%s', $version, uniqid(), $firmwareFile->guessExtension() ?? 'bin');

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
}
