<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\SubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('price')
            ->add('stock', IntegerType::class, [
                'label' => 'Stock initial',
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir le stock initial.'),
                    new Range(
                        min: 0,
                        notInRangeMessage: 'Le stock initial doit être supérieur ou égal à zéro.',
                    ),
                ],
            ])
            ->add('subCategories', EntityType::class, [
                'class' => SubCategory::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '1024k',
                        maxSizeMessage: 'Votre image doit faire moins de 1024 ko.',
                        mimeTypes: ['image/jpeg', 'image/png'],
                        mimeTypesMessage: 'Votre image doit être au format valide (.jpg, .png, .jpeg).',
                        extensions: ['jpg', 'png', 'jpeg'],
                        extensionsMessage: 'Votre image doit être au format valide (.jpg, .png, .jpeg).',
                    ),
                ],
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png,image/jpeg,image/png',
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
