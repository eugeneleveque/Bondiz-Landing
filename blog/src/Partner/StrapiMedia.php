<?php

namespace App\Partner;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Adresse publique des fichiers de Strapi. Ceux qu'il stocke lui-même ont une
 * adresse relative (/uploads/...), à préfixer par STRAPI_PUBLIC_URL ; ceux de
 * Supabase ont déjà une adresse complète.
 */
class StrapiMedia
{
    public function __construct(
        #[Autowire('%env(default::STRAPI_PUBLIC_URL)%')]
        private readonly ?string $strapiPublicUrl = null,
    ) {
    }

    public function url(?string $url): ?string
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
}
