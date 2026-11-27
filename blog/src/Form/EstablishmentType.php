<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fiche modifiable par le partenaire. Les données sont un simple tableau,
 * envoyé à Strapi (voir toApiData / fromApiData). Le nom, la catégorie et
 * l'e-mail restent gérés par l'équipe.
 */
class EstablishmentType extends AbstractType
{
    public const JOURS = [
        'monday' => 'Lundi',
        'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi',
        'friday' => 'Vendredi',
        'saturday' => 'Samedi',
        'sunday' => 'Dimanche',
    ];

    private const CHAMPS = ['address', 'postalCode', 'city', 'phone', 'website', 'description'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $texte = static fn (string $label, int $max, array $attr = [], array $constraints = []) => [
            'label' => $label,
            'required' => false,
            'attr' => $attr + ['maxlength' => $max],
            'constraints' => [new Assert\Length(max: $max), ...$constraints],
        ];

        $builder
            ->add('address', TextType::class, $texte('Adresse', 255, ['placeholder' => '12 rue Saint-Laud']))
            ->add('postalCode', TextType::class, $texte('Code postal', 5, ['inputmode' => 'numeric'], [
                new Assert\Regex('/^\d{5}$/', message: 'Le code postal comporte 5 chiffres.'),
            ]))
            ->add('city', TextType::class, $texte('Ville', 255))
            ->add('phone', TelType::class, $texte('Téléphone', 30, ['placeholder' => '02 41 00 00 00']))
            ->add('website', UrlType::class, $texte('Site web', 255, ['placeholder' => 'https://…'], [
                new Assert\Url(protocols: ['http', 'https'], message: 'Indiquez une adresse web valide.', requireTld: true),
            ]) + ['default_protocol' => null])
            ->add('description', TextareaType::class, $texte('Description', 3000, [
                'rows' => 7,
                'placeholder' => 'Ce qu’on fait chez vous, l’ambiance, ce qui vous rend unique…',
            ]));

        foreach (self::JOURS as $jour => $libelle) {
            $builder->add($jour, TextType::class, $texte($libelle, 60, ['placeholder' => '12h – 23h ou Fermé']));
        }
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return array<string, mixed>
     */
    public static function fromApiData(array $profile): array
    {
        $data = [];
        foreach (self::CHAMPS as $champ) {
            $data[$champ] = $profile[$champ] ?? null;
        }
        foreach (array_keys(self::JOURS) as $jour) {
            $data[$jour] = $profile['openingHours'][$jour] ?? null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function toApiData(array $data): array
    {
        $profile = [];
        foreach (self::CHAMPS as $champ) {
            $profile[$champ] = $data[$champ] ?? null;
        }
        foreach (array_keys(self::JOURS) as $jour) {
            $profile['openingHours'][$jour] = $data[$jour] ?? null;
        }

        return $profile;
    }
}
