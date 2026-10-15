<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Partenaire connecté à l'espace partenaire. Il n'existe pas en base du blog :
 * il est construit à partir de la réponse de Strapi et gardé en session avec
 * son jeton.
 */
class PartnerUser implements UserInterface
{
    /**
     * @param array<string, mixed> $partner
     */
    public function __construct(
        private readonly string $token,
        private readonly array $partner,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getDocumentId(): string
    {
        return $this->partner['documentId'];
    }

    public function getName(): string
    {
        return $this->partner['name'] ?? $this->getUserIdentifier();
    }

    public function getEmail(): string
    {
        return $this->partner['email'] ?? '';
    }

    public function getCity(): ?string
    {
        return $this->partner['city'] ?? null;
    }

    /**
     * Le jeton Strapi est valable 7 jours : au-delà, le partenaire doit se reconnecter.
     */
    public function isTokenExpired(): bool
    {
        $payload = explode('.', $this->token)[1] ?? '';
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        return !\is_array($claims) || !isset($claims['exp']) || $claims['exp'] <= time();
    }

    public function getRoles(): array
    {
        return ['ROLE_PARTNER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->partner['email'] ?? $this->partner['documentId'];
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
