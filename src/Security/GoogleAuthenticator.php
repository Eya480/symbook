<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GoogleAuthenticator extends AbstractOAuthAuthenticator
{
    protected string $serviceName = "google";

    // Ajoutez cette méthode pour retourner le nom du service
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    protected function getUserFromResourceOwner(ResourceOwnerInterface $resourceOwner, UtilisateurRepository $repository): ?Utilisateur
    {
        if (!($resourceOwner instanceof GoogleUser)) {
            throw new \RuntimeException("Expecting Google user");
        }

        // Vérifier que l'email est vérifié
        if (true !== ($resourceOwner->toArray()['email_verified'] ?? null)) {
            throw new AuthenticationException("Email not verified by Google");
        }

        $email = $resourceOwner->getEmail();
        $googleId = $resourceOwner->getId();

        // Chercher d'abord par Google ID
        $user = $repository->findOneBy(['googleId' => $googleId]);
        if ($user) {
            return $user;
        }

        // Si non trouvé, chercher par email
        $user = $repository->findOneBy(['email' => $email]);

        // Si l'utilisateur existe déjà avec cet email, on met à jour son Google ID
        if ($user && !$user->getGoogleId()) {
            $user->setGoogleId($googleId);
            $this->entityManager->flush();
            return $user;
        }

        // Si aucun utilisateur trouvé, retourner null pour laisser le service créer un nouveau compte
        return null;
    }
}