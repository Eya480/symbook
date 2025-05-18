<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\OAuthRegistrationService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

abstract class AbstractOAuthAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly \Symfony\Component\Routing\RouterInterface $router,
        private readonly UtilisateurRepository $repository,
        private readonly OAuthRegistrationService $registrationService
    ) {}

    public function supports(Request $request): ?bool
    {
        return 'auth_oauth_check' === $request->attributes->get('_route')
            && $request->get('service') === $this->getServiceName();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Rediriger directement vers la page d'accueil ou l'espace utilisateur
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('home'));
    }

// Supprimez la méthode needsProfileCompletion() car elle ne sera plus utilisée

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }
        return new RedirectResponse($this->router->generate('auth_oauth_login'));
    }

    // ... (le reste du fichier reste identique)

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $credentials = $this->fetchAccessToken($this->getClient());
        $resourceOwner = $this->getResourceOwnerFromCredentials($credentials);

        // Chercher l'utilisateur existant
        $user = $this->getUserFromResourceOwner($resourceOwner, $this->repository);

        $isNewUser = false;
        if (null === $user) {
            // Créer un nouvel utilisateur si aucun trouvé
            $user = $this->registrationService->persist($resourceOwner);
            $isNewUser = true;
        }

        // Stocker dans la session si c'est un nouvel utilisateur
        if ($request->hasSession()) {
            $request->getSession()->set('is_new_oauth_user', $isNewUser);
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn () => $user),
            [new RememberMeBadge()]
        );
    }

// ... (le reste du fichier reste identique)

    protected function getResourceOwnerFromCredentials(AccessToken $credentials): ResourceOwnerInterface
    {
        return $this->getClient()->fetchUserFromToken($credentials);
    }

    private function getClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient($this->getServiceName());
    }

    /**
     * Détermine si l'utilisateur a besoin de compléter son profil.
     */
    private function needsProfileCompletion(Utilisateur $user): bool
    {
        return $user->getTel() === '00000000'
            || $user->getRue() === 'À compléter'
            || $user->getVille() === null
            || empty($user->getPassword());
    }

    abstract protected function getUserFromResourceOwner(ResourceOwnerInterface $resourceOwner, UtilisateurRepository $repository): ?Utilisateur;
}