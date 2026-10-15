<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Les partenaires ne sont chargés qu'à la connexion (PartnerAuthenticator) :
 * ensuite, le partenaire gardé en session est réutilisé tant que son jeton
 * Strapi est valide.
 *
 * @implements UserProviderInterface<PartnerUser>
 */
class PartnerUserProvider implements UserProviderInterface
{
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof PartnerUser) {
            throw new UnsupportedUserException(\sprintf('Utilisateur "%s" non pris en charge.', $user::class));
        }

        if ($user->isTokenExpired()) {
            throw new UserNotFoundException('Session partenaire expirée.');
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return PartnerUser::class === $class || is_subclass_of($class, PartnerUser::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new UserNotFoundException('Les partenaires se connectent uniquement via le formulaire de connexion.');
    }
}
