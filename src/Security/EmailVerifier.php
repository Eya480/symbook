<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


class EmailVerifier
{
    private $verifyEmailHelper;
    private $mailer;
    private $router;

    public function __construct(VerifyEmailHelperInterface $helper, MailerInterface $mailer, UrlGeneratorInterface $router)
    {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, Utilisateur $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $email = (new TemplatedEmail())
            ->from(new Address('eyaelgharbi889@gmail.com', 'symbook'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAt' => $signatureComponents->getExpiresAt(),
                'user' => $user,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            dump('Erreur envoi email: ' . $e->getMessage());
        }
    }
    public function handleEmailConfirmation(Request $request, Utilisateur $user): void
    {
        try {
            $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, $user->getId(), $user->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            throw $e;
        }

        $user->setIsVerified(true);
    }
}
