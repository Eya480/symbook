<?php

namespace App\Controller;

use App\Entity\Livres;
use App\Form\LivresType;
use App\Repository\LivresRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class LivresController extends AbstractController
{
    /* create livre**********************************************************
    #[Route('admin/livres/create', name: 'livres_create')]
    public function create(EntityManagerInterface $em): Response
    {
        $date = new \DateTime("2016-08-01");

        $livre = new Livres();
        $livre->setTitre('Titre 1')
            ->setEditeur('Editeur 1')
            ->setDateEdition($date)
            ->setResume('Résumé 1')
            ->setIsbn('234-565-54')
            ->setImage('https://picsum.photos/200/?id=4')
            ->setPrix(120)
            ->setSlag('titre-1'); // Correction de "slag" en "slug"

        // Injection de dépendance de l'EntityManager pour gérer la persistance
        $em->persist($livre);
        $em->flush(); // Enregistrement effectif en base de données

        return new Response('Created new book with name: ' . $livre->getTitre());
    }
*/
    // show livre**********************************************************
    #[Route('admin/livres/show/{id}', name: 'livres_show')]
    public function show(Livres $Livre): Response
    {
        if (!$Livre) {
            throw $this->createNotFoundException("Le livre n'existe pas.");
        }
        return $this->render('livres/show.html.twig', [
            'livre' => $Livre
        ]);

        //dd($Livre); // Debugging, à remplacer par une vue si nécessaire
    }
    /*
    // update livre**********************************************************
    #[Route('admin/livres/update/{id}', name: 'livres_update')]
    public function update(EntityManagerInterface $em, Livres $liv): Response
    {
        if (!$liv) {
            throw $this->createNotFoundException("Le livre n'existe pas.");
        }
        return $this->render('livres/update.html.twig', [
            'livre' => $liv
        ]);
    }
        */
    // delete livre**********************************************************
    #[Route('admin/livres/delete/{id}', name: 'livres_delete')]
    public function delete(LivresRepository $rep, $id, EntityManagerInterface $em): Response
    {

        $livre = $rep->find($id);
        if (!$livre) {
            throw $this->createNotFoundException("Le livre n'existe pas.");
        }
        $em->remove($livre);
        $em->flush();
        return new Response('Livre a été supprimée avec succés');
    }

    // Rechercher un livre par titre
    #[Route('admin/livres/show2', name: 'livres_show2')]
    public function show2(LivresRepository $rep): Response
    {
        $livre = $rep->findOneBy(['titre' => 'Titre 1']);

        if (!$livre) {
            return new Response('Aucun livre trouvé avec ce titre.');
        }

        dd($livre);
    }

    // Rechercher des livres par titre et éditeur, triés par prix décroissant
    #[Route('admin/livres/show3', name: 'livres_show3')]
    public function show3(LivresRepository $rep): Response
    {
        $livres = $rep->findBy(
            ['titre' => 'Titre 1', 'editeur' => 'Editeur 1'],
            ['prix' => 'DESC']
        );

        if (!$livres) {
            return new Response('Aucun livre trouvé avec ces critères.');
        }

        dd($livres);
    }

    // ***********************************************************************
    // Lister tous les livres avec un affichage via un template Twig
    #[Route('/admin/livres', name: 'admin_livres')]
    public function listAll(LivresRepository $rep, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $rep->findAll();
        $livres = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            10 /* limit per page */
        );

        return $this->render('livres/listAll.html.twig', [
            'livres' => $livres
        ]);
    }


    #[Route('/admin/livres/create', name: 'admin_livres_create')]
    public function create(Request $req, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $livre = new Livres();
        $form = $this->createForm(LivresType::class, $livre);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception("Erreur lors de l'upload de l'image");
                }

                $livre->setImage($newFilename);
            }

            $em->persist($livre);
            $em->flush();
            $this->addFlash('success', 'Le Livre a été ajouté');
            return $this->redirectToRoute('admin_livres');
        }

        return $this->render('livres/createLiv.html.twig', [
            'f' => $form,
        ]);
    }


    #[Route('/admin/livres/update/{id}', name: 'admin_livres_update')]
    public function update(Request $req, EntityManagerInterface $em, Livres $l): Response
    {

        //affichage du form
        $form = $this->createForm(LivresType::class, $l);
        //traitement
        $form->handleRequest($req); //permet de lier de form a l'objet categorie
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($l);
            $em->flush();
            $this->addFlash('success', 'Le Livre a été modifiée dans la base');
            return $this->redirectToRoute('admin_livres');
        }

        return $this->render('livres/modifierLiv.html.twig', [
            'f' => $form,
        ]);
    }
}
