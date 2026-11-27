<?php

namespace App\Partner;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Gestion des partenaires par l'équipe (/api/partner-admin de Strapi).
 *
 * Les appels sont signés par le secret BLOG_API_SECRET, identique des deux
 * côtés. Il reste sur le serveur : il n'est jamais envoyé au navigateur.
 */
class PartnerAdminApi
{
    public const STATES = [
        'pending' => 'En attente',
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'rejected' => 'Refusé',
    ];

    public function __construct(
        private readonly HttpClientInterface $strapiClient,
        #[Autowire('%env(default::BLOG_API_SECRET)%')]
        private readonly ?string $secret = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== (string) $this->secret;
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return $this->request('GET', '/api/partner-admin/partners');
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed> la fiche créée, avec le mot de passe généré
     */
    public function create(array $data): array
    {
        return $this->request('POST', '/api/partner-admin/partners', ['json' => ['data' => $data]]);
    }

    public function setState(string $documentId, string $state): void
    {
        $this->request('PUT', '/api/partner-admin/partners/'.rawurlencode($documentId).'/state', [
            'json' => ['data' => ['states' => $state]],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new PartnerApiException('La variable BLOG_API_SECRET n’est pas définie sur le blog.', 503);
        }

        try {
            $response = $this->strapiClient->request($method, $path, $options + ['auth_bearer' => $this->secret]);
            $status = $response->getStatusCode();

            try {
                $body = $response->toArray(false);
            } catch (DecodingExceptionInterface) {
                $body = [];
            }
        } catch (ExceptionInterface) {
            throw new PartnerApiException('L’API Bondiz est injoignable. Réessayez dans quelques instants.', 503);
        }

        if ($status >= 400) {
            throw new PartnerApiException(match (true) {
                401 === $status || 403 === $status => 'L’API a refusé le secret : vérifiez que BLOG_API_SECRET est identique des deux côtés.',
                404 === $status => 'Route introuvable sur l’API : sa nouvelle version est-elle bien déployée ?',
                $status >= 500 => 'L’API Bondiz a rencontré une erreur.',
                default => $body['error']['message'] ?? 'La requête a été refusée.',
            }, $status);
        }

        return $body['data'] ?? [];
    }
}
