<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Form\CategoriesType;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted("ROLE_ADMIN")]
final class CategoriesController extends AbstractController
{

    #[Route('/admin/categories', name: 'admin_categories')]
    public function index(CategoriesRepository $catRep, Request $req, PaginatorInterface $paginator): Response
    {
        $searchTerm = $req->query->get('q');

        $categories = $searchTerm
            ? $catRep->findBySearchTerm($searchTerm)
            : $catRep->findAll();

        $categories = $paginator->paginate(
            $categories, /* query NOT result */
            $req->query->getInt('page', 1), /* page number */
            10 /* limit per page */
        );
        return $this->render('categories/index.html.twig', [
            'cat' => $categories,
        ]);
    }



    #[Route('/admin/categories/create', name: 'admin_categories_create')]
    public function create(Request $req, EntityManagerInterface $em): Response
    {
        $cat = new Categories($req);

        //affichage du form
        $form = $this->createForm(CategoriesType::class, $cat);
        //traitement
        $form->handleRequest($req); //permet de lier de form a l'objet categorie
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cat);
            $em->flush();
            $this->addFlash('success', 'La categorie a été ajoutée dans la base');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('categories/createCat.html.twig', [
            'f' => $form,
        ]);
    }

    #[Route('/admin/categories/update/{id}', name: 'admin_categories_update')]
    public function update(Request $req, EntityManagerInterface $em, Categories $c): Response
    {
        $form = $this->createForm(CategoriesType::class, $c);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($c);
            $em->flush();
            $this->addFlash('success', 'La catégorie a été modifiée dans la base');
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('categories/modifierCat.html.twig', [
            'f' => $form->createView(),
        ]);
    }


    #[Route('admin/categories/delete/{id}', name: 'categorie_delete')]
    public function delete(CategoriesRepository $rep, $id, EntityManagerInterface $em): Response
    {

        $cat = $rep->find($id);
        if (!$cat) {
            throw $this->createNotFoundException("La categorie n'existe pas.");
        }
        $em->remove($cat);
        $em->flush();
        $this->addFlash('success', 'La categorie a été supprimé avec succés');
        return $this->redirectToRoute('admin_categories');
    }
}
