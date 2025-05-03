<?php

namespace App\Controller;

use App\Repository\LivresRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(LivresRepository $r): Response
    {
        $liv = $r->findAll();
        return $this->render('home/home.html.twig', [
            'livres' => $liv
        ]);
    }

    #[Route('/livres', name: 'home_livre')]
    public function livres(LivresRepository $r): Response
    {
        $liv = $r->findAll();
        return $this->render('home/livres.html.twig', [
            'livres' => $liv
        ]);
    }
}
