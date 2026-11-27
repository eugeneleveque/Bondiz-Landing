<?php

namespace App\Form;

use App\Partner\PublicPartners;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** Création d'un partenaire depuis l'administration. */
class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = [];
        foreach (PublicPartners::CATEGORIES as $value => $label) {
            $categories[$label ?? 'Autre'] = $value;
        }

        $court = new Assert\Length(max: 255);

        $builder
            ->add('name', TextType::class, ['label' => 'Nom de l’établissement', 'constraints' => [new Assert\NotBlank(), $court]])
            ->add('category', ChoiceType::class, ['label' => 'Catégorie', 'choices' => $categories, 'placeholder' => 'Choisir…', 'constraints' => [new Assert\NotBlank()]])
            ->add('email', EmailType::class, ['label' => 'E-mail (identifiant de connexion)', 'constraints' => [new Assert\NotBlank(), new Assert\Email(), $court]])
            ->add('contactFirstName', TextType::class, ['label' => 'Prénom du contact', 'required' => false, 'constraints' => [$court]])
            ->add('contactLastName', TextType::class, ['label' => 'Nom du contact', 'required' => false, 'constraints' => [$court]])
            ->add('address', TextType::class, ['label' => 'Adresse', 'required' => false, 'constraints' => [$court]])
            ->add('postalCode', TextType::class, ['label' => 'Code postal', 'required' => false, 'constraints' => [new Assert\Regex('/^\d{5}$/', 'Le code postal comporte 5 chiffres.')]])
            ->add('city', TextType::class, ['label' => 'Ville', 'required' => false, 'constraints' => [$court]])
            ->add('states', ChoiceType::class, [
                'label' => 'État',
                'choices' => ['Actif (visible tout de suite)' => 'active', 'En attente (non publié)' => 'pending'],
            ]);
    }
}
