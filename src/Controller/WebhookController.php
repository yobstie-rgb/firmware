<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api', name: 'api_')]
class WebhookController extends AbstractController
{
    private const EXTERNAL_WEBHOOK_URL = 'https://student-attendance-management-163962.hostingersite.com/hcgi/api/webhooks/absences';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    #[Route('/webhook/esp32', name: 'esp32_receiver', methods: ['POST'])]
    public function receiveEsp32Data(Request $request): JsonResponse
    {
        // 1. Récupération et décodage du JSON
        $content = $request->getContent();
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            return $this->json([
                'status'  => 'error',
                'message' => 'Format JSON invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        // 2. Validation des champs obligatoires
        $requiredFields = ['event_id', 'matricule', 'date', 'heure', 'statut', 'motif'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                return $this->json([
                    'status'  => 'error',
                    'message' => sprintf('Champ manquant : %s', $field)
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // 3. Transmission à l'URL externe (Relais)
        try {
            $externalResponse = $this->httpClient->request('POST', self::EXTERNAL_WEBHOOK_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'event_id'  => $data['event_id'],
                    'matricule' => $data['matricule'],
                    'date'      => $data['date'],
                    'heure'     => $data['heure'],
                    'statut'    => $data['statut'],
                    'motif'     => $data['motif'],
                ],
                'timeout' => 10, // Timeout de 10 secondes
            ]);

            $statusCode = $externalResponse->getStatusCode();

            // Si le serveur distant ne renvoie pas un statut 2xx (ex: 200, 201)
            if ($statusCode < 200 || $statusCode >= 300) {
                return $this->json([
                    'status'  => 'error',
                    'message' => sprintf('Erreur du service distant (Code HTTP %d)', $statusCode)
                ], Response::HTTP_BAD_GATEWAY);
            }

        } catch (\Throwable $e) {
            // Gestion des erreurs réseau ou de timeout
            return $this->json([
                'status'  => 'error',
                'message' => 'Échec de la transmission à l\'API externe : ' . $e->getMessage()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // 4. Succès : confirmation envoyée à l'ESP32
        return $this->json([
            'status'  => 'success',
            'message' => 'Données reçues et transmises avec succès',
            'event'   => $data['event_id']
        ], Response::HTTP_OK);
    }
}
