<?php

namespace App\Controller;

use App\Form\VideoType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class VideoController extends AbstractController
{
    #[Route('/video/add', name: 'app_video_add', methods: ['GET', 'POST'])]
    public function add(Request $request, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(VideoType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $videoFile = $form->get('videoFile')->getData();

            if ($videoFile) {
                $originalFilename = pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $videoFile->guessExtension();

                try {
                    // Sauvegarde du fichier dans public/uploads/videos
                    $videoFile->move(
                        $this->getParameter('videos_directory'),
                        $newFilename
                    );

                    $this->addFlash('success', 'La vidéo "' . $data['title'] . '" a été enregistrée avec succès sous le nom : ' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement de la vidéo.');
                    return $this->redirectToRoute('app_video_add');
                }
            }

            return $this->redirectToRoute('app_video_list');
        }

        return $this->render('video/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/video/list', name: 'app_video_list', methods: ['GET'])]
    public function list(): Response
    {
        $videosDirectory = $this->getParameter('videos_directory');
        $videos = [];

        if (file_exists($videosDirectory)) {
            $finder = new Finder();
            $finder->files()->in($videosDirectory);

            foreach ($finder as $file) {
                $videos[] = [
                    'name' => $file->getFilename(),
                    'size' => round($file->getSize() / (1024 * 1024), 2),
                ];
            }
        }

        return $this->render('video/list.html.twig', [
            'videos' => $videos,
        ]);
    }

    #[Route('/video/stream/{filename}', name: 'app_video_stream', methods: ['GET'])]
    public function streamVideo(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('videos_directory') . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('La vidéo n\'existe pas.');
        }

        $response = new BinaryFileResponse($filePath);
        // Détection automatique du type MIME (video/mp4, video/webm, etc.)
        $response->headers->set('Content-Type', mime_content_type($filePath));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        return $response;
    }
}
