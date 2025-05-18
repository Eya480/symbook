<?php

namespace App\Controller;

use App\Repository\LivresRepository;
use App\Repository\CategoriesRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function livres(LivresRepository $livresRepository, CategoriesRepository $categoriesRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $searchTerm = $request->query->get('q');
        $categorieId = $request->query->get('categorie');
        $prixRange = $request->query->get('prix');

        $query = $livresRepository->createQueryBuilder('l');

        if ($searchTerm) {
            $query->andWhere('l.titre LIKE :searchTerm OR l.resume LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($categorieId) {
            $query->join('l.categorie', 'c')
                ->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        // Filtre par prix
        if ($prixRange) {
            // Définir les plages de prix
            $ranges = [
                '10-50' => ['min' => 10, 'max' => 50],
                '50-100' => ['min' => 50, 'max' => 100],
                '100-500' => ['min' => 100, 'max' => 500],
                '500+' => ['min' => 500, 'max' => null],
            ];
            if (isset($ranges[$prixRange])) {
                $min = $ranges[$prixRange]['min'];
                $max = $ranges[$prixRange]['max'];

                if ($max !== null) {
                    $query->andWhere('l.prix BETWEEN :min AND :max')
                        ->setParameter('min', $min)
                        ->setParameter('max', $max);
                } else {
                    $query->andWhere('l.prix >= :min')
                        ->setParameter('min', $min);
                }
            }
        }

        $livres = $query->getQuery();

        $livres = $paginator->paginate(
            $livres, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            9 /* limit per page */
        );

        $lesPrix = array_keys([
            '10-50' => '10 - 50 DT',
            '50-100' => '50 - 100 DT',
            '100-500' => '100 - 500 DT',
            '500+' => '500 DT et plus',
        ]);

        return $this->render('home/livres.html.twig', [
            'livres' => $livres,
            'categories' => $categoriesRepository->findAll(),
            'lesPrix' => $lesPrix
        ]);
    }
}
