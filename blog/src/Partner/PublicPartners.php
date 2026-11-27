<?php

namespace App\Partner;

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

    /** Libellés des catégories Strapi (champ « category » des partenaires). */
    public const CATEGORIES = [
        'restaurant' => 'Restaurant',
        'bar' => 'Bar',
        'cafe' => 'Café',
        'bowling' => 'Bowling',
        'escape_game' => 'Escape game',
        'cinema' => 'Cinéma',
        'museum' => 'Musée',
        'sport' => 'Sport',
        'karaoke' => 'Karaoké',
        'concert_venue' => 'Salle de concert',
        'other' => null,
    ];

    public function __construct(
        private readonly HttpClientInterface $strapiClient,
        private readonly CacheInterface $cache,
        private readonly StrapiMedia $media,
    ) {
    }

    /**
     * @return list<array{name: string, logo: ?string, logoAlt: string, photo: ?string, category: ?string, city: ?string}>
     */
    public function list(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            try {
                $response = $this->strapiClient->request('GET', '/api/partners', [
                    'query' => [
                        'fields' => ['name', 'category', 'city'],
                        'populate' => [
                            'logo' => ['fields' => ['url', 'alternativeText']],
                            'coverImage' => ['fields' => ['url', 'alternativeText']],
                        ],
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

    /** Après un changement depuis l'administration : ne pas attendre le cache. */
    public function forget(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * @param array<string, mixed> $partner
     *
     * @return array{name: string, logo: ?string, logoAlt: string, photo: ?string, category: ?string, city: ?string}|null
     */
    private function format(array $partner): ?array
    {
        $name = trim((string) ($partner['name'] ?? ''));
        if ('' === $name) {
            return null;
        }

        // « coverImage » accepte plusieurs photos : la première illustre la carte.
        return [
            'name' => $name,
            'logo' => $this->media->url($partner['logo']['url'] ?? null),
            'logoAlt' => $partner['logo']['alternativeText'] ?? 'Logo de '.$name,
            'photo' => $this->media->url($partner['coverImage'][0]['url'] ?? null),
            'category' => self::CATEGORIES[$partner['category'] ?? ''] ?? null,
            'city' => $partner['city'] ?? null,
        ];
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
