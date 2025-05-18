<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('prenom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('tel', TelType::class, [
                'attr' => ['placeholder' => '0612345678'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{10}$/',
                        'message' => 'Le numéro doit contenir 10 chiffres (ex: 0612345678)'
                    ])
                ]
            ])
            ->add('rue', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La rue est obligatoire']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('codePostal', TextType::class, [
                'attr' => ['placeholder' => '75000'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code postal est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Le code postal doit contenir 5 chiffres (ex: 75000)'
                    ])
                ]
            ])
            ->add('carteCredit', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => '1234567812345678'],
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{16}$/',
                        'message' => 'La carte doit contenir 16 chiffres'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Email(['message' => 'Veuillez entrer un email valide'])
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => false,
                'multiple' => true,
                'label' => 'Rôles',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Au moins un rôle doit être sélectionné'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('isVerified')
            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => 'nomVille', // Correction ici : utilisation de nomVille
                'placeholder' => 'Sélectionnez une ville',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La ville est obligatoire'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}