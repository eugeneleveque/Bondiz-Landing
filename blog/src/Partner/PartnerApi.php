<?php

namespace App\Partner;

use App\Security\PartnerUser;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client de l'espace partenaire de Strapi (/api/partner-portal).
 *
 * Les partenaires et leurs activités vivent dans Strapi, comme le reste des
 * données de l'application : le blog n'en garde aucune copie.
 */
class PartnerApi
{
    public function __construct(private readonly HttpClientInterface $strapiClient)
    {
    }

    /**
     * @return array{token: string, partner: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        return $this->request('POST', '/api/partner-portal/login', null, [
            'json' => ['email' => $email, 'password' => $password],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivities(PartnerUser $partner): array
    {
        return $this->request('GET', '/api/partner-portal/activities', $partner)['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getActivity(PartnerUser $partner, string $documentId): array
    {
        return $this->request('GET', $this->activityPath($documentId), $partner)['data'];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function createActivity(PartnerUser $partner, array $data): array
    {
        return $this->request('POST', '/api/partner-portal/activities', $partner, ['json' => ['data' => $data]])['data'];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function updateActivity(PartnerUser $partner, string $documentId, array $data): array
    {
        return $this->request('PUT', $this->activityPath($documentId), $partner, ['json' => ['data' => $data]])['data'];
    }

    public function deleteActivity(PartnerUser $partner, string $documentId): void
    {
        $this->request('DELETE', $this->activityPath($documentId), $partner);
    }

    private function activityPath(string $documentId): string
    {
        return '/api/partner-portal/activities/'.rawurlencode($documentId);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?PartnerUser $partner, array $options = []): array
    {
        if (null !== $partner) {
            $options['auth_bearer'] = $partner->getToken();
        }

        try {
            $response = $this->strapiClient->request($method, $path, $options);
            $status = $response->getStatusCode();

            if (204 === $status) {
                return [];
            }

            $body = $response->toArray(false);
        } catch (ExceptionInterface) {
            throw new PartnerApiException('Le service Bondiz est momentanément indisponible. Réessayez dans quelques instants.', 503);
        }

        if ($status >= 400) {
            throw new PartnerApiException($this->errorMessage($status, $body), $status);
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function errorMessage(int $status, array $body): string
    {
        return match (true) {
            429 === $status => 'Trop de tentatives. Patientez quelques minutes avant de réessayer.',
            $status >= 500 => 'Le service Bondiz a rencontré une erreur. Réessayez dans quelques instants.',
            default => $body['error']['message'] ?? 'La requête a été refusée.',
        };
    }
}
