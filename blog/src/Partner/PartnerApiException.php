<?php

namespace App\Partner;

/**
 * Erreur renvoyée par l'espace partenaire de Strapi. Le message est en
 * français et peut être affiché tel quel au partenaire.
 */
class PartnerApiException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode)
    {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function isUnauthorized(): bool
    {
        return 401 === $this->statusCode;
    }
}
