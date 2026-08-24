<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class OtaController extends AbstractController
{
    #[Route('/api/ota/download', name: 'ota_download')]
    public function downloadFirmware(): BinaryFileResponse
    {
        // Chemin vers le fichier binaire généré par PlatformIO (.pio/build/esp32dev/firmware.bin)
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/firmware.bin';

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'firmware.bin'
        );

        return $response;
    }
}
