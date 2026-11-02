<?php

namespace App\Entity;

use App\Repository\ContactMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message envoyé depuis la page « Nous contacter ». Il est consulté dans
 * l'administration, puis traité par l'équipe.
 */
#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Indiquez votre nom.')]
    #[Assert\Length(max: 120)]
    private ?string $lastName = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Indiquez votre prénom.')]
    #[Assert\Length(max: 120)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indiquez votre adresse e-mail.')]
    #[Assert\Email(message: 'Cette adresse e-mail n’est pas valide.')]
    #[Assert\Length(max: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Indiquez l’objet de votre message.')]
    #[Assert\Length(max: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Écrivez votre message.')]
    #[Assert\Length(max: 5000)]
    private ?string $message = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /** Message traité par l'équipe (coché depuis l'administration). */
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

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

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
