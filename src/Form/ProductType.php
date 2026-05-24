<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank()],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
                'invalid_message' => 'Le prix doit être un nombre valide.',
                'constraints' => [new NotBlank(), new Range(min: 0)],
            ])
            ->add('image', TextType::class, [
                'label' => 'Chemin de l\'image',
                'help' => 'Chemin relatif dans public/assets/images/',
            ])
            ->add('categories', EntityType::class, [
                'label' => 'Catégories',
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false,
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
