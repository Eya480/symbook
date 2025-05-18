<?php

namespace App\Form;

use App\Entity\Commande;
use App\Enum\EStatutCom;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;

// Pour les types de champs
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach (EStatutCom::cases() as $case) {
            $choices[$case->getLabel()] = $case;
        }
        $builder
            ->add('dateCommande', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de la commande',
                'required' => true,
            ])

            ->add('status', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($case) => $case->getLabel(), EStatutCom::cases()),
                    array_map(fn($case) => $case->value, EStatutCom::cases())
                ),
                'choice_label' => function ($value) {
                    // $value est une string, valeur de l'enum
                    return EStatutCom::from($value)->getLabel();
                },
                'choice_value' => fn(?string $value) => $value,
                'label' => 'Statut',
                'required' => true,
            ])

            ->add('montantTotal', MoneyType::class, [
                'currency' => 'DT',
                'label' => 'Total (DT)',
                'required' => true,
            ]);

        $builder->get('status')->addModelTransformer(new CallbackTransformer(
            fn(?EStatutCom $enum) => $enum?->value,
            fn(?string $value) => $value ? EStatutCom::from($value) : null
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
