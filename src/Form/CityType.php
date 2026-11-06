<?php

namespace App\Form;

use App\Entity\City;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

final class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la ville',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir le nom de la ville.'),
                    new Length(max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control'],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir le code postal.'),
                    new Length(exactly: 5, exactMessage: 'Le code postal doit contenir 5 caractères.'),
                    new Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres.'),
                ],
                'attr' => [
                    'maxlength' => 5,
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]{5}',
                    'class' => 'form-control',
                ],
            ])
            ->add('shippingCost', NumberType::class, [
                'label' => 'Frais de port (€)',
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir les frais de port.'),
                    new Range(min: 0, max: 99999999.99, notInRangeMessage: 'Le tarif doit être compris entre {{ min }} et {{ max }} €.'),
                ],
                'attr' => ['min' => 0, 'max' => '99999999.99', 'step' => '0.01', 'class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }
}
