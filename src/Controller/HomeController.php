<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home',methods: ['GET'])]
    public function index(): Response //JsonResponse
    {

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
        
        // return $this->json([
        //     'message' => 'Welcome to your new controller!',
        //     'path' => 'src/Controller/HomeController.php',
        // ]);
    }
}
