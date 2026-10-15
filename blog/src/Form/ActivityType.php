<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Activité proposée par un partenaire. Les données sont un simple tableau,
 * envoyé à Strapi (voir toApiData / fromApiData).
 */
class ActivityType extends AbstractType
{
    private const TIMEZONE = 'Europe/Paris';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dateOptions = [
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'model_timezone' => 'UTC',
            'view_timezone' => self::TIMEZONE,
            'required' => false,
        ];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Soirée quiz entre inconnus', 'maxlength' => 255],
                'constraints' => [new Assert\NotBlank(message: 'Donnez un titre à l’activité.'), new Assert\Length(max: 255)],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 6, 'placeholder' => 'Ce qui attend les participants, ce qui est inclus…'],
                'constraints' => [new Assert\Length(max: 5000)],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => ['placeholder' => 'Adresse ou nom de la salle', 'maxlength' => 255],
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('startDate', DateTimeType::class, ['label' => 'Début'] + $dateOptions)
            ->add('endDate', DateTimeType::class, ['label' => 'Fin'] + $dateOptions)
            ->add('minParticipants', IntegerType::class, [
                'label' => 'Participants minimum',
                'required' => false,
                'attr' => ['min' => 1],
                'constraints' => [new Assert\Positive()],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Participants maximum',
                'required' => false,
                'attr' => ['min' => 1],
                'constraints' => [new Assert\Positive()],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix par personne (€)',
                'scale' => 2,
                'required' => false,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.01'],
                'constraints' => [new Assert\PositiveOrZero()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [new Assert\Callback($this->validateRanges(...))],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateRanges(array $data, ExecutionContextInterface $context): void
    {
        if ($data['startDate'] && $data['endDate'] && $data['endDate'] < $data['startDate']) {
            $context->buildViolation('La fin doit être après le début.')->atPath('[endDate]')->addViolation();
        }
        if ($data['minParticipants'] && $data['maxParticipants'] && $data['minParticipants'] > $data['maxParticipants']) {
            $context->buildViolation('Le minimum ne peut pas dépasser le maximum.')->atPath('[minParticipants]')->addViolation();
        }
    }

    /**
     * Données du formulaire -> corps attendu par Strapi.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function toApiData(array $data): array
    {
        $date = static fn (?\DateTimeImmutable $value) => $value?->format(\DATE_ATOM);

        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'],
            'startDate' => $date($data['startDate']),
            'endDate' => $date($data['endDate']),
            'minParticipants' => $data['minParticipants'],
            'maxParticipants' => $data['maxParticipants'],
            'price' => $data['price'],
        ];
    }

    /**
     * Activité renvoyée par Strapi -> données du formulaire.
     *
     * @param array<string, mixed> $activity
     *
     * @return array<string, mixed>
     */
    public static function fromApiData(array $activity): array
    {
        // Strapi renvoie "…Z" : le formulaire exige le fuseau nommé "UTC" (model_timezone).
        $date = static fn (?string $value) => $value
            ? (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('UTC'))
            : null;

        return [
            'title' => $activity['title'],
            'description' => $activity['description'],
            'location' => $activity['location'],
            'startDate' => $date($activity['startDate']),
            'endDate' => $date($activity['endDate']),
            'minParticipants' => $activity['minParticipants'],
            'maxParticipants' => $activity['maxParticipants'],
            'price' => $activity['price'],
        ];
    }
}
