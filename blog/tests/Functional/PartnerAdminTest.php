<?php

namespace App\Tests\Functional;

use App\Entity\PartnershipRequest;
use App\Entity\User;
use App\Tests\StrapiSimule;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

/** Onglet « Partenaires » de l'administration, avec un Strapi simulé. */
final class PartnerAdminTest extends WebTestCase
{
    private KernelBrowser $client;
    private StrapiSimule $strapi;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $_SERVER['BLOG_API_SECRET'] = $_ENV['BLOG_API_SECRET'] = 'secret-de-test';
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('HTTP_ORIGIN', 'http://localhost');
        $this->strapi = static::getContainer()->get(StrapiSimule::class);

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $schema = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $admin = (new User())->setEmail('admin@test.fr')->setPseudo('Admin')->setRoles(['ROLE_ADMIN'])->setPassword('x');
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);
    }

    private static function json(mixed $body, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($body, \JSON_THROW_ON_ERROR), ['http_code' => $status]);
    }

    public function testCreationDepuisUneDemande(): void
    {
        $demande = (new PartnershipRequest())
            ->setEstablishmentName('Le Bar Test')->setActivityType('Bar')->setCity('Angers')
            ->setContactName('Léa Martin')->setEmail('bar@test.fr');
        $this->em->persist($demande);
        $this->em->flush();
        $id = $demande->getId();

        $fiche = ['documentId' => 'p1', 'name' => 'Le Bar Test', 'email' => 'bar@test.fr', 'city' => 'Angers', 'states' => 'active'];
        $this->strapi->reponses['POST /api/partner-admin/partners'] = self::json(['data' => $fiche + ['password' => 'MotDePasseUnique']], 201);
        $this->strapi->reponses['GET /api/partner-admin/partners'] = self::json(['data' => [$fiche]]);

        $crawler = $this->client->request('GET', '/admin/partenaires/nouveau?demande='.$id);
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Créer le partenaire')->form(['partner[category]' => 'bar']));
        self::assertResponseRedirects();
        $this->client->followRedirect();

        // Aucun envoi configuré en test : le mot de passe reste affiché à l'administrateur.
        self::assertSelectorTextContains('.alert-warning', 'MotDePasseUnique');
        $envoi = $this->strapi->requetes('POST')[0];
        self::assertSame('bar@test.fr', $envoi['body']['data']['email']);
        self::assertSame(['Authorization: Bearer secret-de-test'], $envoi['headers']['authorization']);

        $this->em->clear();
        self::assertTrue($this->em->find(PartnershipRequest::class, $id)->isHandled());

        // Le mot de passe n'est montré qu'une fois.
        $this->client->request('GET', '/admin/partenaires');
        self::assertStringNotContainsString('MotDePasseUnique', (string) $this->client->getResponse()->getContent());
    }

    public function testErreurDeLApiAffichee(): void
    {
        $this->strapi->reponses['GET /api/partner-admin/partners'] = self::json([], 401);

        $this->client->request('GET', '/admin/partenaires');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert-danger', 'BLOG_API_SECRET');
    }

    public function testMessagerieEtReponse(): void
    {
        $message = ['content' => 'Bonjour l’équipe', 'fromTeam' => false, 'createdAt' => '2026-11-27T10:00:00.000Z'];
        $this->strapi->reponses['GET /api/partner-admin/conversations'] = self::json(['data' => [
            ['documentId' => 'p1', 'name' => 'Le Bar Test', 'lastMessage' => $message, 'unread' => 1],
        ]]);
        $this->strapi->reponses['GET /api/partner-admin/partners/p1/messages'] = self::json(['data' => [
            'partner' => ['documentId' => 'p1', 'name' => 'Le Bar Test', 'email' => 'bar@test.fr'],
            'messages' => [$message],
        ]]);
        $this->strapi->reponses['POST /api/partner-admin/partners/p1/messages'] = self::json(['data' => []], 201);

        $this->client->request('GET', '/admin/partenaires/messages');
        self::assertSelectorTextContains('table', 'Bonjour l’équipe');

        $crawler = $this->client->request('GET', '/admin/partenaires/p1/messages');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Envoyer')->form(['message' => 'Bienvenue']));

        self::assertResponseRedirects();
        self::assertSame(['content' => 'Bienvenue'], $this->strapi->requetes('POST')[0]['body']['data']);
    }
}
