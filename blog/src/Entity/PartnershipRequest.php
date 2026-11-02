<?php

namespace App\Entity;

use App\Repository\PartnershipRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Demande envoyée depuis la page « Devenir partenaire ». Elle est consultée
 * dans l'administration, puis traitée par l'équipe.
 */
#[ORM\Entity(repositoryClass: PartnershipRequestRepository::class)]
class PartnershipRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indiquez le nom de votre établissement.')]
    #[Assert\Length(max: 255)]
    private ?string $establishmentName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indiquez votre type d’activité.')]
    #[Assert\Length(max: 255)]
    private ?string $activityType = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Indiquez votre ville.')]
    #[Assert\Length(max: 120)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indiquez le nom et le prénom du contact.')]
    #[Assert\Length(max: 255)]
    private ?string $contactName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indiquez votre adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n’est pas valide.')]
    #[Assert\Length(max: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(pattern: '/^[0-9 +().-]*$/', message: 'Ce numéro de téléphone n’est pas valide.')]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 3000)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** Demande traitée par l'équipe (cochée depuis l'administration). */
    #[ORM\Column]
    private bool $handled = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEstablishmentName(): ?string
    {
        return $this->establishmentName;
    }

    public function setEstablishmentName(?string $establishmentName): static
    {
        $this->establishmentName = $establishmentName;

        return $this;
    }

    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    public function setActivityType(?string $activityType): static
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): static
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled): static
    {
        $this->handled = $handled;

        return $this;
    }
}
