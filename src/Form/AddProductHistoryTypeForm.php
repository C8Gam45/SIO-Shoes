<?php

namespace App\Form;

use App\Entity\AddProductHistory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class AddProductHistoryTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('qte', IntegerType::class, [
                'label' => 'Quantité',
                'constraints' => [
                    new Range(
                        min: 1,
                        notInRangeMessage: 'La quantité doit être supérieure à zéro.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddProductHistory::class,
        ]);
    }
}
