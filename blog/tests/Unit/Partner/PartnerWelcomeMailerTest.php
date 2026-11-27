<?php

namespace App\Tests\Unit\Partner;

use App\Partner\PartnerWelcomeMailer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PartnerWelcomeMailerTest extends TestCase
{
    private function mailer(MailerInterface $mailer, string $dsn = 'smtp://localhost'): PartnerWelcomeMailer
    {
        $urls = $this->createStub(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturn('https://bondiz.fr/partenaires/connexion');

        return new PartnerWelcomeMailer($mailer, $urls, new NullLogger(), $dsn, 'contact@bondiz.fr', 'Support Bondiz');
    }

    public function testEnvoieLesIdentifiantsAuPartenaire(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(static function (Email $email): bool {
            return 'bar@test.fr' === $email->getTo()[0]->getAddress()
                && str_contains((string) $email->getTextBody(), 'Mot de passe : Secret123')
                && str_contains((string) $email->getTextBody(), 'https://bondiz.fr/partenaires/connexion');
        }));

        self::assertTrue($this->mailer($mailer)->send('Le Bar', 'bar@test.fr', 'Secret123'));
    }

    public function testSignaleUnEnvoiImpossible(): void
    {
        $enPanne = $this->createStub(MailerInterface::class);
        $enPanne->method('send')->willThrowException(new TransportException('panne'));
        self::assertFalse($this->mailer($enPanne)->send('Le Bar', 'bar@test.fr', 'x'));

        $muet = $this->createMock(MailerInterface::class);
        $muet->expects(self::never())->method('send');
        self::assertFalse($this->mailer($muet, 'null://null')->send('Le Bar', 'bar@test.fr', 'x'));
    }
}
