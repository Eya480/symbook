<?php

namespace App\Controller;

use App\Entity\Livres;
use App\Repository\LivresRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LivresController extends AbstractController
{
    #[Route('/livres/create', name: 'livres_create')]
    public function create(EntityManagerInterface $em): Response
    {
        $date = new DateTime("2016-08-01");
        $livre = new Livres();
        $livre->setTitre('Titre 1')
            ->setEditeur('Editeur 1')->setDateEdition($date)
            ->setResume('Resume 1')->setIsbn('234-565-54')
            ->setImage('https://picsum.photos/200/?id=4')
            ->setPrix(120)
            ->setSlag('titre-1');
        //on a la depandance que on doit l'ajouter la dans le parametre de la fonction => injection de la depandance => EntityManager
        $em->persist($livre);
        $em->flush(); // taadeha ll base 9bal bsh lid tekheth valeur
        //dd($livre);
        return new Response('Created new book with name : ' . $livre->getTitre());
    }

    #[Route('/livres/show/{id}', name: 'livres_show')]
    public function show(LivresRepository $rep, $id): Response
    {
        $livre = $rep->find($id);
        if (!$livre) {
            throw $this->createNotFoundException("livre ayant id = $id n'existe pas");
        }

        dd($livre);
    }
    //chercher par titre
    #[Route('/livres/show2', name: 'livres_show2')]
    public function show2(LivresRepository $rep): Response
    {
        $livre = $rep->findOneBy(['Titre' => 'Titre 1']);
        dd($livre);
    }

    #[Route('/livres/show3', name: 'livres_show3')]
    public function show3(LivresRepository $rep): Response
    {
        $livre = $rep->findBy(['Titre' => 'Titre 1', 'editeur' => 'Duno'], ['prix' => 'ASC']);
        dd($livre);
    }
    //find all , travail avec les templates
}
