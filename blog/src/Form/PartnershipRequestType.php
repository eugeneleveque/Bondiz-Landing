<?php

namespace App\Form;

use App\Entity\PartnershipRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class PartnershipRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('establishmentName', TextType::class, [
                'label' => 'Nom de l’établissement*',
                'attr' => ['autocomplete' => 'organization'],
            ])
            ->add('activityType', TextType::class, [
                'label' => 'Type d’activité (bar, café, atelier créatif, sport, etc.)*',
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville*',
                'attr' => ['autocomplete' => 'address-level2'],
            ])
            ->add('contactName', TextType::class, [
                'label' => 'Nom et prénom du contact*',
                'attr' => ['autocomplete' => 'name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail professionnelle*',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone (facultatif)',
                'required' => false,
                'attr' => ['autocomplete' => 'tel'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Parlez-nous de votre activité',
                'required' => false,
            ])
            ->add('consent', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [new IsTrue(message: 'Merci d’accepter l’utilisation de vos informations pour que nous puissions vous répondre.')],
            ])
            // Champ piège invisible : les robots le remplissent, pas les humains.
            ->add('website', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Site web',
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PartnershipRequest::class,
            'csrf_token_id' => 'partnership_request',
        ]);
    }
}
