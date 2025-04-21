<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Form\CategoriesType;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class CategoriesController extends AbstractController{
    #[Route('/admin/categories', name: 'admin_categories')]
    public function index(CategoriesRepository $catRep): Response
    {
        $cat= $catRep->findAll();
        return $this->render('categories/index.html.twig', [
            'cat' => $cat,
        ]);
    }

    #[Route('/admin/categories/create', name: 'admin_categories_create')]
    public function create(Request $req,EntityManagerInterface $em): Response
    {
        $cat= new Categories($req);

        //affichage du form
        $form=$this->createForm(CategoriesType::class,$cat);
        //traitement
        $form->handleRequest($req);//permet de lier de form a l'objet categorie
        if($form->isSubmitted() && $form->isValid()){
            $em->persist($cat);
            $em->flush();
            $this->addFlash('success','La categorie a été ajoutée dans la base');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('categories/createCat.html.twig', [
            'f' => $form,
        ]);
        
    }

    #[Route('/admin/categories/update', name: 'admin_categories_update')]
    public function update(Request $req,EntityManagerInterface $em,Categories $c): Response
    {

        //affichage du form
        $form=$this->createForm(CategoriesType::class,$c);
        //traitement
        $form->handleRequest($req);//permet de lier de form a l'objet categorie
        if($form->isSubmitted() && $form->isValid()){
            $em->persist($c);
            $em->flush();
            $this->addFlash('success','La categorie a été modifiée dans la base');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('categories/modifierCat.html.twig', [
            'f' => $form,
        ]);
        
    }
}
