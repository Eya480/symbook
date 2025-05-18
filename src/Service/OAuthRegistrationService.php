<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Repository\UtilisateurRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OAuthRegistrationService
{
    public function __construct(
        private UtilisateurRepository $userRepository,
        private VilleRepository $villeRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function persist(ResourceOwnerInterface $resourceOwner): Utilisateur
    {
        if (!($resourceOwner instanceof GoogleUser)) {
            throw new \RuntimeException("Expecting Google user");
        }

        $user = new Utilisateur();
        $user->setEmail($resourceOwner->getEmail());
        $user->setGoogleId($resourceOwner->getId());
        $user->setRoles(['ROLE_USER']);

        // Générer un mot de passe factice (ne sera jamais utilisé pour la connexion Google)
        $user->setPassword(bin2hex(random_bytes(16))); // ou utiliser 'google_oauth' comme valeur

        // Valeurs par défaut pour les autres champs obligatoires
        $user->setNom('Non spécifié');
        $user->setPrenom('Non spécifié');
        $user->setTel('00000000');
        $user->setRue('À compléter');
        $user->setCodePostal('00000');

        // Gestion de la Ville
        $ville = $this->entityManager->getRepository(Ville::class)
            ->findOneBy(['nomVille' => 'Inconnue']);

        if (!$ville) {
            $ville = new Ville();
            $ville->setNomVille('Inconnue');
            $this->entityManager->persist($ville);
        }
        $user->setVille($ville);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }}