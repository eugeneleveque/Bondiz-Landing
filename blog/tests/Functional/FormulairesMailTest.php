<?php

namespace App\Tests\Functional;

use App\Entity\ContactMessage;
use App\Entity\PartnershipRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Formulaires « Nous contacter » et « Devenir partenaire » : enregistrement en
 * base et e-mails envoyés (transport null://null en test, les messages sont
 * tout de même capturés par Symfony).
 */
final class FormulairesMailTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    /** @return list<Email> */
    private function emails(): array
    {
        return array_values(array_filter($this->getMailerMessages(), static fn ($m) => $m instanceof Email));
    }

    private static function adresses(array $adresses): array
    {
        return array_map(static fn (Address $a) => $a->toString(), $adresses);
    }

    private function envoyerContact(array $champs = []): void
    {
        $crawler = $this->client->request('GET', '/nous-contacter');
        $this->client->submit($crawler->selectButton('Envoyer un message')->form($champs + [
            'contact_message[lastName]' => 'Martin',
            'contact_message[firstName]' => 'Léa',
            'contact_message[email]' => 'lea.martin@exemple.fr',
            'contact_message[subject]' => 'Question sur les activités',
            'contact_message[message]' => 'Bonjour, comment proposer une activité ?',
            'contact_message[consent]' => true,
        ]));
    }

    private function envoyerDemandePartenariat(array $champs = []): void
    {
        $crawler = $this->client->request('GET', '/devenir-partenaire');
        $this->client->submit($crawler->selectButton('Envoyer ma demande de partenariat')->form($champs + [
            'partnership_request[establishmentName]' => 'Le Bar du Coin',
            'partnership_request[activityType]' => 'Bar à jeux',
            'partnership_request[city]' => 'Angers',
            'partnership_request[contactName]' => 'Paul Durand',
            'partnership_request[email]' => 'paul@barducoin.fr',
            'partnership_request[phone]' => '0600000000',
            'partnership_request[message]' => 'Nous organisons des soirées jeux.',
            'partnership_request[consent]' => true,
        ]));
    }

    public function testUnMessageDeContactPrevientLEquipe(): void
    {
        $this->envoyerContact();

        self::assertResponseRedirects('/nous-contacter#formulaire');
        $emails = $this->emails();
        self::assertCount(1, $emails);

        $email = $emails[0];
        self::assertSame(['"Support Bondiz" <contact@bondiz.fr>'], self::adresses($email->getFrom()));
        self::assertSame(['contact@bondiz.fr'], self::adresses($email->getTo()));
        self::assertSame(['lea.martin@exemple.fr'], self::adresses($email->getReplyTo()));
        self::assertSame('[Contact] Question sur les activités', $email->getSubject());
        self::assertStringContainsString('comment proposer une activité', $email->getTextBody());

        self::assertCount(1, static::getContainer()->get(EntityManagerInterface::class)->getRepository(ContactMessage::class)->findAll());

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Votre message a bien été envoyé');
    }

    public function testUneDemandeDePartenariatPrevientLEquipeEtConfirmeAuPartenaire(): void
    {
        $this->envoyerDemandePartenariat();

        self::assertResponseRedirects('/devenir-partenaire#formulaire');
        $emails = $this->emails();
        self::assertCount(2, $emails);

        [$equipe, $confirmation] = $emails;
        self::assertSame(['contact@bondiz.fr'], self::adresses($equipe->getTo()));
        self::assertSame(['paul@barducoin.fr'], self::adresses($equipe->getReplyTo()));
        self::assertSame('Nouvelle demande de partenariat : Le Bar du Coin', $equipe->getSubject());

        self::assertSame(['"Support Bondiz" <contact@bondiz.fr>'], self::adresses($confirmation->getFrom()));
        self::assertSame(['paul@barducoin.fr'], self::adresses($confirmation->getTo()));
        self::assertSame(['contact@bondiz.fr'], self::adresses($confirmation->getReplyTo()));
        self::assertSame('Bondiz : nous avons bien reçu votre demande de partenariat', $confirmation->getSubject());
        self::assertStringContainsString('Bonjour Paul Durand,', $confirmation->getTextBody());
        self::assertStringContainsString('pour Le Bar du Coin', $confirmation->getTextBody());

        self::assertCount(1, static::getContainer()->get(EntityManagerInterface::class)->getRepository(PartnershipRequest::class)->findAll());
    }

    public function testUnRobotQuiRemplitLeChampPiegeNEnvoieRien(): void
    {
        $this->envoyerContact(['contact_message[website]' => 'https://spam.example']);
        self::assertResponseRedirects('/nous-contacter#formulaire');
        self::assertSame([], $this->emails());

        $this->envoyerDemandePartenariat(['partnership_request[website]' => 'https://spam.example']);
        self::assertSame([], $this->emails());
    }

    public function testUnFormulaireInvalideNEnvoieRien(): void
    {
        $this->envoyerContact(['contact_message[email]' => 'pas-un-email']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->emails());
    }
}
