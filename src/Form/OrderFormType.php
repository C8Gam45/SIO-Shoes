<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Order;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\httpFoundation\JsonResponse;
final class OrderFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir le prénom.'),
                    new Length(max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir le nom.'),
                    new Length(max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control'],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir le numéro de téléphone.'),
                    new Length(max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control'],
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir l’adresse.'),
                    new Length(max: 255),
                ],
                'attr' => ['maxlength' => 255, 'class' => 'form-control'],
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => static fn (City $city): string => $city->getZipCode().' - '.$city->getName(),
                'choice_attr' => static fn (City $city): array => [
                    'data-shipping-cost' => $city->getShippingCost(),
                ],
                'query_builder' => static fn (EntityRepository $repository) => $repository
                    ->createQueryBuilder('city')
                    ->orderBy('city.zipCode', 'ASC')
                    ->addOrderBy('city.name', 'ASC'),
                'placeholder' => 'Choisir une ville',
                'label' => 'Ville de livraison',
                'attr' => ['class' => 'form-select', 'id' => 'delivery-city'],
            ])
            ->add('shippingPrice', HiddenType::class, [
                'mapped' => false,
                'attr' => ['id' => 'shipping-price'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
