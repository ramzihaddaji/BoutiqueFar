<?php

declare(strict_types=1);

namespace App\Form\Category;

use App\DTO\Category\CreateCategoryDTO;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentCategoryId = $options['current_category_id'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => [
                    'placeholder' => 'Ex: Vêtements, Hommes, Chaussures...',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (facultative)',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description sommaire de la catégorie...',
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Catégorie active (visible dans le catalogue)',
                'required' => false,
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie parente',
                'required' => false,
                'placeholder' => '-- Aucune (Catégorie racine) --',
                'query_builder' => function (CategoryRepository $repository) use ($currentCategoryId): QueryBuilder {
                    $qb = $repository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');

                    // Évite l'auto-parenting direct dès le sélecteur si on édite une catégorie existante
                    if ($currentCategoryId !== null) {
                        $qb->andWhere('c.id != :currentId')
                            ->setParameter('currentId', $currentCategoryId);
                    }

                    return $qb;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateCategoryDTO::class,
            'current_category_id' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'category_item',
        ]);

        $resolver->setAllowedTypes('current_category_id', ['null', 'int']);
    }
}

