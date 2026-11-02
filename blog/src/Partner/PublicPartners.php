<?php

namespace App\Partner;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Partenaires publiés dans Strapi, pour la page publique « Nos partenaires ».
 *
 * La liste est mise en cache quelques minutes : la page ne doit ni ralentir
 * ni tomber si Strapi est lent ou indisponible. En cas d'erreur, elle reçoit
 * une liste vide et affiche un message d'attente.
 */
class PublicPartners
{
    private const CACHE_KEY = 'bondiz_public_partners';
    private const CACHE_TTL = 600;
    private const LIMIT = 12;

    public function __construct(
        private readonly HttpClientInterface $strapiClient,
        private readonly CacheInterface $cache,
        #[Autowire('%env(default::STRAPI_PUBLIC_URL)%')]
        private readonly ?string $strapiPublicUrl = null,
    ) {
    }

    /**
     * @return list<array{name: string, logo: ?string, logoAlt: string}>
     */
    public function list(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                $response = $this->strapiClient->request('GET', '/api/partners', [
                    'query' => [
                        'fields' => ['name'],
                        'populate' => ['logo' => ['fields' => ['url', 'alternativeText']]],
                        'sort' => 'name:asc',
                        'pagination' => ['pageSize' => self::LIMIT],
                    ],
                ]);

                if (200 !== $response->getStatusCode()) {
                    return $this->unavailable($item);
                }

                $partners = $response->toArray(false)['data'] ?? [];
            } catch (ExceptionInterface) {
                return $this->unavailable($item);
            }

            return array_values(array_filter(array_map($this->format(...), $partners)));
        });
    }

    /**
     * @param array<string, mixed> $partner
     *
     * @return array{name: string, logo: ?string, logoAlt: string}|null
     */
    private function format(array $partner): ?array
    {
        $name = trim((string) ($partner['name'] ?? ''));
        if ('' === $name) {
            return null;
        }

        return [
            'name' => $name,
            'logo' => $this->absoluteUrl($partner['logo']['url'] ?? null),
            'logoAlt' => $partner['logo']['alternativeText'] ?? 'Logo de '.$name,
        ];
    }

    /**
     * Les fichiers stockés par Strapi lui-même ont une adresse relative
     * (/uploads/...), à préfixer par son adresse publique.
     */
    private function absoluteUrl(?string $url): ?string
    {
        if (null === $url || '' === $url) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (!$this->strapiPublicUrl) {
            return null;
        }

        return rtrim($this->strapiPublicUrl, '/').'/'.ltrim($url, '/');
    }

    /**
     * Strapi injoignable : on réessaie plus tôt que le délai normal.
     *
     * @return list<never>
     */
    private function unavailable(ItemInterface $item): array
    {
        $item->expiresAfter(60);

        return [];
    }
}
