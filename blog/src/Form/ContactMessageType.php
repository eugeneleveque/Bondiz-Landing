<?php

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom*',
                'attr' => ['autocomplete' => 'family-name'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom*',
                'attr' => ['autocomplete' => 'given-name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail*',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('subject', TextType::class, [
                // Espace insécable : le « ? » ne passe pas seul à la ligne
                'label' => "Quel est l’objet de votre message\u{00A0}?*",
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message*',
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
            'data_class' => ContactMessage::class,
            'csrf_token_id' => 'contact_message',
        ]);
    }
}
