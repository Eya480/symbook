<?php

namespace App\Form;

use App\Entity\Livres;
use App\Entity\Categories;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class LivresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('slag')
            ->add('resume')
            ->add('isbn')
            ->add('image')
            ->add('editeur')
            ->add('dateEdition', null, [
                'widget' => 'single_text'
            ])
            ->add('prix')
            ->add('categorie', EntityType::class, [
                'class' => Categories::class,
'choice_label' => 'libelle',
            ])
            ->add('Save',SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livres::class,
        ]);
    }
}
