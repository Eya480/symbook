<?php

namespace App\Controller;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use App\Repository\LivresRepository;
use App\Repository\CategoriesRepository;
use BotMan\BotMan\Drivers\DriverManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;

final class BotmanController extends AbstractController
{
    #[Route('/botman', name: 'app_botman', methods: ['GET', 'POST'])]
    public function handle(Request $request, LivresRepository $livresRepository, CategoriesRepository $categoriesRepository, LoggerInterface $logger): Response
    {
        $session = $request->getSession();
        $session->start();

        ob_start(function ($buffer) use ($logger) {
            if ($buffer) {
                $logger->warning('Captured output in main buffer', ['output' => $buffer]);
            }
            return ''; 
        });

        // Log incoming request
        $logger->info('Botman request received', [
            'method' => $request->getMethod(),
            'content' => $request->getContent(),
            'headers' => $request->headers->all(),
        ]);
        DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

        $config = [
            'web' => [
                'matchingData' => [
                    'driver' => 'web',
                ],
            ],
        ];

        $botman = BotManFactory::create($config);

        $responses = [];

        $addResponse = function ($type, $text, $attachment = null, $additional = []) use (&$responses, $logger) {
            $responses[] = [
                'type' => $type,
                'text' => $text,
                'attachment' => $attachment,
                'additionalParameters' => $additional,
            ];
            $logger->debug('Added response', ['text' => $text]);
        };
        $botman->hears('(hello|hi|hey|bonjour|salut)', function (BotMan $bot) use ($addResponse) {
            $addResponse('text', 'Bonjour ! Je suis votre assistant de librairie. Comment puis-je vous aider ?');
        });

        $botman->hears('(comment vas-tu|ça va|comment ça va)', function (BotMan $bot) use ($addResponse) {
            $addResponse('text', 'Je suis un bot, donc je fonctionne parfaitement bien ! Comment puis-je vous aider ?');
        });

        $botman->hears('(recherche|cherche|trouve) (livre|livres) {query}', function (BotMan $bot, $query) use ($livresRepository, $addResponse) {
            $livres = $livresRepository->createQueryBuilder('l')
                ->where('l.titre LIKE :query OR l.editeur LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();

            if (empty($livres)) {
                $addResponse('text', 'Aucun livre trouvé pour "' . $query . '".');
            } else {
                $message = 'Voici les livres correspondants à votre recherche :';
                foreach ($livres as $livre) {
                    $message .= "\n\n📖 *" . $livre->getTitre() . "*";
                    $message .= "\n🏷️ Prix: " . $livre->getPrix() . " DT";
                    $message .= "\n🏢 Éditeur: " . $livre->getEditeur();
                    $message .= "\n🔖 Catégorie: " . ($livre->getCategorie() ? $livre->getCategorie()->getLibelle() : 'Non catégorisé');
                    $message .= "\n📅 Date d'édition: " . ($livre->getDateEdition() ? $livre->getDateEdition()->format('d/m/Y') : 'Date inconnue');
                }
                $addResponse('text', $message);
            }
        });
        $botman->hears('(catégorie|categorie|categories) {nom}', function (BotMan $bot, $nom) use ($categoriesRepository, $livresRepository, $addResponse) {
            $categorie = $categoriesRepository->findOneBy(['libelle' => $nom]);

            if (!$categorie) {
                $addResponse('text', 'Aucune catégorie nommée "' . $nom . '" trouvée.');
                return;
            }

            $livres = $livresRepository->findBy(['categorie' => $categorie], ['titre' => 'ASC'], 5);

            $message = "📚 Catégorie: *" . $categorie->getLibelle() . "*";
            if ($categorie->getDescription()) {
                $message .= "\nDescription: " . $categorie->getDescription();
            }
            $message .= "\n\nLivres disponibles (" . count($livres) . "):";

            foreach ($livres as $livre) {
                $message .= "\n- " . $livre->getTitre() . " (" . $livre->getPrix() . " DT)";
            }

            $message .= "\n\nPour plus de détails sur un livre, demandez-moi : \"livre [titre]\"";
            $addResponse('text', $message);
        });

        $botman->hears('(livre|detail|détail) {titre}', function (BotMan $bot, $titre) use ($livresRepository, $addResponse) {
            $livre = $livresRepository->createQueryBuilder('l')
                ->where('l.titre LIKE :titre')
                ->setParameter('titre', '%' . $titre . '%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$livre) {
                $addResponse('text', 'Désolé, je n\'ai pas trouvé de livre correspondant à "' . $titre . '".');
                return;
            }

            $message = "📖 *Fiche du livre* 📖";
            $message .= "\n\n*Titre*: " . $livre->getTitre();
            $message .= "\n*Éditeur*: " . $livre->getEditeur();
            $message .= "\n*Prix*: " . $livre->getPrix() . " DT";
            $message .= "\n*Catégorie*: " . ($livre->getCategorie() ? $livre->getCategorie()->getLibelle() : 'Non catégorisé');
            $message .= "\n*Date d'édition*: " . ($livre->getDateEdition() ? $livre->getDateEdition()->format('d/m/Y') : 'Date inconnue');
            $message .= "\n*ISBN*: " . ($livre->getIsbn() ?: 'Non disponible');
            $message .= "\n*Résumé*: " . ($livre->getResume() ?: 'Non disponible');
            $message .= "\n*Stock disponible*: " . ($livre->getStock() !== null ? $livre->getStock() : 'Information non disponible');

            $addResponse('text', $message);
        });

        $botman->hears('(livres|livre) (moins de|jusqu\'à|max|maximum) {prix} DT', function (BotMan $bot, $prix) use ($livresRepository, $addResponse) {
            $livres = $livresRepository->createQueryBuilder('l')
                ->where('l.prix <= :prix')
                ->setParameter('prix', $prix)
                ->orderBy('l.prix', 'ASC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();

            if (empty($livres)) {
                $addResponse('text', 'Aucun livre trouvé à moins de ' . $prix . ' DT.');
            } else {
                $message = 'Livres à moins de ' . $prix . ' DT :';
                foreach ($livres as $livre) {
                    $message .= "\n- " . $livre->getTitre() . " (" . $livre->getPrix() . " DT)";
                }
                $addResponse('text', $message);
            }
        });

        $botman->hears('(livres|livre) (éditeur|editeur|publié par) {editeur}', function (BotMan $bot, $editeur) use ($livresRepository, $addResponse) {
            $livres = $livresRepository->createQueryBuilder('l')
                ->where('l.editeur LIKE :editeur')
                ->setParameter('editeur', '%' . $editeur . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();

            if (empty($livres)) {
                $addResponse('text', 'Aucun livre trouvé pour l\'éditeur "' . $editeur . '".');
            } else {
                $message = 'Livres publiés par ' . $editeur . ' :';
                foreach ($livres as $livre) {
                    $message .= "\n- " . $livre->getTitre() . " (" . $livre->getPrix() . " DT)";
                }
                $addResponse('text', $message);
            }
        });

        // Aide et commandes disponibles
        $botman->hears('(aide|help|commandes|que peux-tu faire)', function (BotMan $bot) use ($addResponse) {
            $message = "Voici ce que je peux faire pour vous :\n\n";
            $message .= "🔍 *Recherche de livres* :\n";
            $message .= "- \"recherche livre [titre ou éditeur]\"\n";
            $message .= "- \"livre [titre exact]\" (pour les détails complets)\n\n";
            $message .= "📚 *Par catégorie* :\n";
            $message .= "- \"catégorie [nom]\"\n\n";
            $message .= "💰 *Par prix* :\n";
            $message .= "- \"livres moins de [prix] DT\"\n\n";
            $message .= "🏢 *Par éditeur* :\n";
            $message .= "- \"livres éditeur [nom]\"\n\n";
            $message .= "Essayez une de ces commandes !";

            $addResponse('text', $message);
        });

        $botman->fallback(function (BotMan $bot) use ($addResponse) {
            $addResponse('text', 'Je n\'ai pas compris votre demande. Tapez "aide" pour voir les commandes disponibles.');
        });

        // Process incoming messages
        try {
            ob_start();
            $botman->listen();
            $botmanOutput = ob_get_clean();
            if ($botmanOutput) {
                $logger->warning('BotMan listen produced output', ['output' => $botmanOutput]);
            }
        } catch (\Exception $e) {
            $logger->error('Botman listen error', ['exception' => $e->getMessage()]);
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            return new JsonResponse(['error' => 'Error processing request'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Clean main output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Return JSON response
        return new JsonResponse([
            'status' => 200,
            'messages' => $responses,
        ], Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/chatframe', name: 'app_chatframe', methods: ['GET'])]
    public function chatFrame(): Response
    {
        return $this->render('botman/chat_frame.html.twig');
    }
}
