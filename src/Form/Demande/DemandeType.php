<?php

declare(strict_types=1);

namespace App\Form\Demande;

use App\DTO\Demande\CreateDemandeDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientName', TextType::class, [
                'label' => 'Votre nom et prénom',
                'attr' => [
                    'placeholder' => 'Ex: Jean Dupont',
                    'autocomplete' => 'name',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Numéro de téléphone (WhatsApp)',
                'attr' => [
                    'placeholder' => 'Ex: +33 6 12 34 56 78',
                    'autocomplete' => 'tel',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville de résidence',
                'attr' => [
                    'placeholder' => 'Ex: Paris, Lyon, Marseille...',
                    'autocomplete' => 'address-level2',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateDemandeDTO::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'demande_client',
        ]);
    }
}

