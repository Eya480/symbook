<?php

namespace App\Form;

use App\Entity\Livres;
use App\Entity\Categories;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Isbn;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Isbn as IsbnConstraint; // Notez l'alias

class LivresType extends AbstractType
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', null, [
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('isbn', null, [
                'constraints' => [
                    new NotBlank(['message' => 'L\'ISBN est obligatoire']),
                    //new Isbn([
                     //   'type' => 'isbn13',
                     //   'message' => 'Veuillez entrer un ISBN valide (format ISBN-10 ou ISBN-13).',
                     //   'isbn10Message' => 'L\'ISBN-10 "{{ value }}" n\'est pas valide.',
                      //  'isbn13Message' => 'L\'ISBN-13 "{{ value }}" n\'est pas valide.',
                    //]),
                ],
            ])
            ->add('resume', null, [
                'constraints' => [
                    new Length(['min' => 10, 'minMessage' => 'Le résumé doit faire au moins {{ limit }} caractères'])
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du livre (JPG ,PNG, JPG,WEBP)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG/PNG)',
                    ])
                ],
            ])
            ->add('editeur')
            ->add('dateEdition', null, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('prix', null, [
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero(['message' => 'Le prix doit être positif'])
                ]
            ])
            ->add('stock', null, [
                'constraints' => [
                    new NotBlank(),
                    new PositiveOrZero(['message' => 'Le nombres en stock doit être positif'])
                ]
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categories::class,
                'choice_label' => 'libelle',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('Sauvgarder', SubmitType::class)
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $livre = $event->getForm()->getData();

            // Générer le slug à partir du titre
            if (isset($data['titre'])) {
                $slug = $this->slugger->slug($data['titre'])->lower();
                $livre->setSlug($slug);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livres::class,
        ]);
    }
}
