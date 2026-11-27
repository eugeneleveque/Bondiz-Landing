<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** Ajout d'une photo à la fiche. Strapi revérifie le contenu et la taille. */
class PartnerPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('photo', FileType::class, [
            'label' => 'Ajouter une photo (JPEG, PNG ou WebP, 5 Mo maximum)',
            'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
            'constraints' => [
                new Assert\NotNull(message: 'Choisissez une photo.'),
                new Assert\Image(
                    maxSize: '5Mi',
                    mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    maxSizeMessage: 'La photo ne doit pas dépasser 5 Mo.',
                    mimeTypesMessage: 'Formats acceptés : JPEG, PNG ou WebP.',
                    uploadIniSizeErrorMessage: 'La photo ne doit pas dépasser 5 Mo.',
                ),
            ],
        ]);
    }
}
