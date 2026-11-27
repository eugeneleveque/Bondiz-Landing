<?php

namespace App\Partner;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Envoie au nouveau partenaire ses identifiants de connexion. */
class PartnerWelcomeMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urls,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(default:default_mailer_dsn:MAILER_DSN)%')]
        private readonly string $dsn,
        #[Autowire('%env(default:default_contact_email:CONTACT_EMAIL)%')]
        private readonly string $contactEmail,
        #[Autowire('%env(default:default_mail_from_name:MAILER_FROM_NAME)%')]
        private readonly string $fromName,
    ) {
    }

    /**
     * @return bool faux si rien n'est parti : l'administrateur doit alors
     *              transmettre le mot de passe lui-même
     */
    public function send(string $name, string $email, string $password): bool
    {
        // Le transport « null » avale les messages sans signaler d'erreur.
        if (str_starts_with($this->dsn, 'null:')) {
            return false;
        }

        $message = (new Email())
            ->from(new Address($this->contactEmail, $this->fromName))
            ->to($email)
            ->replyTo($this->contactEmail)
            ->subject('Bondiz : votre espace partenaire est ouvert')
            ->text(implode("\n", [
                'Bonjour,',
                '',
                \sprintf('L’espace partenaire de %s est ouvert. Vous pouvez y publier vos activités.', $name),
                '',
                'Adresse de connexion : '.$this->urls->generate('app_partner_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'Identifiant : '.$email,
                'Mot de passe : '.$password,
                '',
                'Conservez ce mot de passe en lieu sûr et ne le transférez à personne.',
                '',
                'Cordialement,',
                'L’équipe Bondiz',
            ]));

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->warning('Partenaire créé, mais identifiants non envoyés.', ['exception' => $exception]);

            return false;
        }

        return true;
    }
}
