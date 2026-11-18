<?php

declare(strict_types=1);

namespace App\Form\Article;

use App\DTO\Article\CreateArticleDTO;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'article',
                'attr' => [
                    'placeholder' => 'Ex: T-Shirt Coton Bio, Baskets Sport...',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Description détaillée de l\'article...',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix de vente',
                'currency' => 'EUR',
                'divisor' => 1,
                'input' => 'string',
                'attr' => [
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit (JPEG, PNG ou WEBP - max 5Mo)',
                'required' => false,
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Disponible immédiatement',
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'fullPath',
                'label' => 'Catégorie finale',
                'placeholder' => '-- Sélectionner une catégorie finale --',
                'query_builder' => function (CategoryRepository $repository): QueryBuilder {
                    // Affiche uniquement les catégories actives ET finales (sans sous-catégories)
                    return $repository->createQueryBuilder('c')
                        ->leftJoin('c.children', 'child')
                        ->where('c.active = :active')
                        ->groupBy('c.id')
                        ->having('COUNT(child.id) = 0')
                        ->setParameter('active', true)
                        ->orderBy('c.name', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateArticleDTO::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'article_item',
        ]);
    }
}

