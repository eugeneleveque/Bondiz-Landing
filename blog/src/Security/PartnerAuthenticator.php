<?php

namespace App\Security;

use App\Partner\PartnerApi;
use App\Partner\PartnerApiException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Connexion des partenaires : le mot de passe est vérifié par Strapi, qui
 * renvoie un jeton gardé dans la session du blog.
 */
class PartnerAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_partner_login';

    public function __construct(
        private readonly PartnerApi $partnerApi,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $payload = $request->getPayload();
        $email = trim($payload->getString('email'));
        $password = $payload->getString('password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Strapi n'est appelé qu'une fois le jeton CSRF vérifié (le chargeur du badge est paresseux).
        $userBadge = new UserBadge($email, function () use ($email, $password): PartnerUser {
            if ('' === $email || '' === $password) {
                throw new CustomUserMessageAuthenticationException('Email et mot de passe requis.');
            }

            try {
                $result = $this->partnerApi->login($email, $password);
            } catch (PartnerApiException $exception) {
                throw new CustomUserMessageAuthenticationException($exception->getMessage());
            }

            return new PartnerUser($result['token'], $result['partner']);
        });

        return new SelfValidatingPassport($userBadge, [
            new CsrfTokenBadge('partner_authenticate', $payload->getString('_csrf_token')),
        ]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_partner_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
