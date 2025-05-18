<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConnectController extends AbstractController
{
    public const SCOPES = [
        'google' => [],
    ];

    #[Route('/connect', name: 'auth_oauth_login')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home_livre');
        }
        return $this->render('connect/index.html.twig');
    }

    #[Route('/logout', name: 'auth_oauth_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Cette méthode est interceptée par le firewall.');
    }

    #[Route('/oauth/connect/{service}', name: 'auth_oauth_connect', methods: ['GET'])]
    public function connect(string $service, ClientRegistry $clientRegistry): RedirectResponse
    {
        if (!in_array($service, array_keys(self::SCOPES), true)) {
            throw $this->createNotFoundException();
        }

        $client = $clientRegistry->getClient($service);
        return $client->redirect(self::SCOPES[$service]);
    }

    #[Route('/oauth/check/{service}', name: 'auth_oauth_check', methods: ['GET'])]
    public function check(string $service): Response
    {
        // Cette méthode est interceptée par Symfony pour finaliser l'authentification
        return new Response('', 200);
    }
}
