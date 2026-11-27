<?php

namespace App\Tests\Functional;

use App\Security\PartnerUser;
use App\Tests\StrapiSimule;
use App\Tests\Unit\Partner\PartnerUserTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Pages de l'espace partenaire, avec un Strapi simulé (voir when@test dans
 * config/services.yaml).
 */
final class PartnerControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private StrapiSimule $strapi;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Même conteneur, donc même Strapi simulé, d'une requête à l'autre.
        $this->client->disableReboot();
        // Comme un navigateur : la protection CSRF sans session vérifie l'origine.
        $this->client->setServerParameter('HTTP_ORIGIN', 'http://localhost');
        $this->strapi = static::getContainer()->get(StrapiSimule::class);
    }

    private static function json(mixed $body, int $status = 200): MockResponse
    {
        return new MockResponse(json_encode($body, \JSON_THROW_ON_ERROR), ['http_code' => $status]);
    }

    private function connecter(): void
    {
        $this->client->loginUser(new PartnerUser(PartnerUserTest::jeton(time() + 3600), [
            'documentId' => 'p1',
            'name' => 'Le Bar Test',
            'email' => 'bar@test.fr',
            'city' => 'Angers',
            'states' => 'active',
        ]), 'partner');
    }

    /** @return array<string, mixed> */
    private static function activite(string $titre, string $debut, array $autres = []): array
    {
        return $autres + [
            'documentId' => 'doc-'.md5($titre),
            'title' => $titre,
            'description' => null,
            'location' => 'Angers',
            'startDate' => $debut,
            'endDate' => null,
            'minParticipants' => null,
            'maxParticipants' => 8,
            'price' => 10,
            'bookingsCount' => 0,
        ];
    }

    public function testUnVisiteurEstRenvoyeVersLaConnexion(): void
    {
        $this->client->request('GET', '/partenaires/activites');

        self::assertResponseRedirects('/partenaires/connexion');
    }

    public function testLaConnexionPasseParStrapi(): void
    {
        $this->strapi->reponses['POST /api/partner-portal/login'] = self::json([
            'token' => PartnerUserTest::jeton(time() + 3600),
            'partner' => ['documentId' => 'p1', 'name' => 'Le Bar Test', 'email' => 'bar@test.fr'],
        ]);
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['data' => []]);

        $crawler = $this->client->request('GET', '/partenaires/connexion');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->filter('form')->form([
            'email' => 'bar@test.fr',
            'password' => 'secret',
        ]));

        self::assertResponseRedirects('/partenaires');
        self::assertSame(['email' => 'bar@test.fr', 'password' => 'secret'], $this->strapi->requetes[0]['body']);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Le Bar Test');
    }

    public function testUnMauvaisMotDePasseAfficheLeMessageDeStrapi(): void
    {
        $this->strapi->reponses['POST /api/partner-portal/login'] = self::json(['error' => ['message' => 'Email ou mot de passe incorrect.']], 401);

        $crawler = $this->client->request('GET', '/partenaires/connexion');
        $this->client->submit($crawler->filter('form')->form(['email' => 'bar@test.fr', 'password' => 'faux']));
        $this->client->followRedirect();

        self::assertSelectorTextContains('body', 'Email ou mot de passe incorrect.');
    }

    public function testMesActivitesListeLesActivitesAVenirEtPassees(): void
    {
        $this->connecter();
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['data' => [
            self::activite('Soirée quiz à venir', (new \DateTimeImmutable('+10 days'))->format(\DATE_ATOM)),
            self::activite('Bowling passé', (new \DateTimeImmutable('-10 days'))->format(\DATE_ATOM)),
        ]]);

        $crawler = $this->client->request('GET', '/partenaires/activites');

        self::assertResponseIsSuccessful();
        self::assertSame(['Soirée quiz à venir', 'Bowling passé'], $crawler->filter('.bz-ligne-activite__titre')->each(fn ($n) => $n->text()));
        self::assertSelectorTextContains('.bz-ligne-activite--passee', 'Terminée');

        $crawler = $this->client->request('GET', '/partenaires/activites?filtre=passees');
        self::assertSame(['Bowling passé'], $crawler->filter('.bz-ligne-activite__titre')->each(fn ($n) => $n->text()));
    }

    public function testUneSessionStrapiExpireeDeconnecteLePartenaire(): void
    {
        $this->connecter();
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['error' => ['message' => 'Unauthorized']], 401);

        $this->client->request('GET', '/partenaires/activites');

        self::assertResponseRedirects('/partenaires/deconnexion');
    }

    public function testCreationDUneActivite(): void
    {
        $this->connecter();
        $this->strapi->reponses['POST /api/partner-portal/activities'] = self::json(['data' => self::activite('Soirée quiz', '2026-12-05T18:00:00.000Z')], 201);
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['data' => [self::activite('Soirée quiz', '2026-12-05T18:00:00.000Z')]]);

        $crawler = $this->client->request('GET', '/partenaires/activites/nouvelle');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Publier l’activité')->form([
            'activity[title]' => 'Soirée quiz',
            'activity[location]' => 'Angers',
            'activity[startDate]' => '2026-12-05T19:00',
            'activity[maxParticipants]' => '8',
            'activity[price]' => '10',
        ]));

        self::assertResponseRedirects('/partenaires/activites');
        $envoi = $this->strapi->requetes('POST')[0];
        self::assertSame('Soirée quiz', $envoi['body']['data']['title']);
        self::assertSame('2026-12-05T18:00:00+00:00', $envoi['body']['data']['startDate']);
        self::assertSame(8, $envoi['body']['data']['maxParticipants']);

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', '« Soirée quiz » est en ligne');
        self::assertSelectorTextContains('.bz-ligne-activite__titre', 'Soirée quiz');
    }

    public function testUnFormulaireInvalideNAppellePasStrapi(): void
    {
        $this->connecter();

        $crawler = $this->client->request('GET', '/partenaires/activites/nouvelle');
        $this->client->submit($crawler->selectButton('Publier l’activité')->form(['activity[title]' => '']));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.partner-field-error', 'Donnez un titre à l’activité.');
        self::assertSame([], $this->strapi->requetes);
    }

    public function testUnRefusDeStrapiEstAfficheSurLeFormulaire(): void
    {
        $this->connecter();
        $this->strapi->reponses['POST /api/partner-portal/activities'] = self::json(['error' => ['message' => 'Le minimum de participants ne peut pas dépasser le maximum.']], 400);

        $crawler = $this->client->request('GET', '/partenaires/activites/nouvelle');
        $this->client->submit($crawler->selectButton('Publier l’activité')->form(['activity[title]' => 'Quiz']));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.bz-alerte--erreur', 'Le minimum de participants ne peut pas dépasser le maximum.');
    }

    public function testLeFormulaireEstRefuseSansOrigineValide(): void
    {
        $this->connecter();
        // Envoi depuis un autre site, tel qu'un navigateur le signale.
        $this->client->setServerParameter('HTTP_ORIGIN', 'https://site-pirate.example');
        $this->client->setServerParameter('HTTP_REFERER', 'https://site-pirate.example/piege');
        $this->client->setServerParameter('HTTP_SEC_FETCH_SITE', 'cross-site');

        $crawler = $this->client->request('GET', '/partenaires/activites/nouvelle');
        $this->client->submit($crawler->selectButton('Publier l’activité')->form(['activity[title]' => 'Quiz']));

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->strapi->requetes);
    }

    public function testModificationDUneActivite(): void
    {
        $this->connecter();
        $activite = self::activite('Quiz', '2026-12-05T18:00:00.000Z', ['documentId' => 'abc']);
        $this->strapi->reponses['GET /api/partner-portal/activities/abc'] = self::json(['data' => $activite]);
        $this->strapi->reponses['PUT /api/partner-portal/activities/abc'] = self::json(['data' => ['title' => 'Blind test'] + $activite]);

        $crawler = $this->client->request('GET', '/partenaires/activites/abc/modifier');
        self::assertResponseIsSuccessful();
        // L'heure de Strapi (UTC) est affichée à l'heure de Paris.
        self::assertSame('2026-12-05T19:00', $crawler->filter('#activity_startDate')->attr('value'));

        $this->client->submit($crawler->selectButton('Enregistrer')->form(['activity[title]' => 'Blind test']));

        self::assertResponseRedirects('/partenaires/activites');
        $envoi = $this->strapi->requetes('PUT')[0];
        self::assertSame('Blind test', $envoi['body']['data']['title']);
        self::assertSame('2026-12-05T18:00:00+00:00', $envoi['body']['data']['startDate']);
    }

    public function testLaSuppressionVerifieLeJetonCsrf(): void
    {
        $this->connecter();

        $this->client->request('POST', '/partenaires/activites/abc/supprimer', ['_token' => 'faux']);

        self::assertResponseRedirects('/partenaires/activites');
        self::assertSame([], $this->strapi->requetes);
    }

    public function testSuppressionDUneActivite(): void
    {
        $this->connecter();
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['data' => [self::activite('Quiz', '2026-12-05T18:00:00.000Z', ['documentId' => 'abc'])]]);
        $this->strapi->reponses['DELETE /api/partner-portal/activities/abc'] = new MockResponse('', ['http_code' => 204]);

        $crawler = $this->client->request('GET', '/partenaires/activites');
        $this->client->submit($crawler->selectButton('Supprimer')->form());

        self::assertResponseRedirects('/partenaires/activites');
        self::assertContains('DELETE', array_column($this->strapi->requetes, 'method'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pagesDeLEspace(): iterable
    {
        yield 'tableau de bord' => ['/partenaires'];
        yield 'statistiques' => ['/partenaires/statistiques'];
        yield 'établissement' => ['/partenaires/etablissement'];
        yield 'messages' => ['/partenaires/messages'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pagesDeLEspace')]
    public function testLesPagesDeLEspaceSAffichent(string $url): void
    {
        $this->connecter();
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['data' => [
            self::activite('Quiz', (new \DateTimeImmutable('+3 days'))->format(\DATE_ATOM), ['bookingsCount' => 3]),
        ]]);

        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    private function simulerLaFiche(array $autres = []): void
    {
        $this->strapi->reponses['GET /api/partner-portal/profile'] = self::json(['data' => $autres + [
            'documentId' => 'p1',
            'name' => 'Le Bar Test',
            'address' => '1 rue du Test',
            'postalCode' => '49000',
            'city' => 'Angers',
            'phone' => '0612345678',
            'website' => null,
            'description' => '<script>alert(1)</script>',
            'openingHours' => ['friday' => '18h – 2h'],
            'photos' => [['id' => 7, 'url' => 'https://images.test/a.jpg']],
        ]]);
        $this->strapi->reponses['GET /api/partner-portal/activities'] = self::json(['data' => []]);
    }

    public function testLaFicheAfficheLesDonneesEchappees(): void
    {
        $this->connecter();
        $this->simulerLaFiche();

        $this->client->request('GET', '/partenaires/etablissement');

        self::assertSelectorTextContains('.bz-horaires', '18h – 2h');
        self::assertSelectorExists('.bz-photo img[src="https://images.test/a.jpg"]');
        self::assertStringNotContainsString('<script>alert(1)</script>', (string) $this->client->getResponse()->getContent());
    }

    public function testModificationDeLaFiche(): void
    {
        $this->connecter();
        $this->simulerLaFiche(['description' => 'Un bar.']);
        $this->strapi->reponses['PUT /api/partner-portal/profile'] = self::json(['data' => []]);

        $crawler = $this->client->request('GET', '/partenaires/etablissement/modifier');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Enregistrer')->form(['establishment[monday]' => 'Fermé']));

        self::assertResponseRedirects('/partenaires/etablissement');
        $envoi = $this->strapi->requetes('PUT')[0]['body']['data'];
        self::assertSame('Fermé', $envoi['openingHours']['monday']);
        self::assertSame('18h – 2h', $envoi['openingHours']['friday']);
        self::assertSame('Un bar.', $envoi['description']);
    }

    public function testAjoutEtSuppressionDUnePhoto(): void
    {
        $this->connecter();
        $this->simulerLaFiche();
        $this->strapi->reponses['POST /api/partner-portal/photos'] = self::json(['data' => []], 201);
        $this->strapi->reponses['DELETE /api/partner-portal/photos/7'] = self::json(['data' => []]);

        $image = tempnam(sys_get_temp_dir(), 'photo').'.png';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        $crawler = $this->client->request('GET', '/partenaires/etablissement');
        $formulaire = $crawler->selectButton('Envoyer')->form();
        $formulaire['partner_photo[photo]']->upload($image);
        $this->client->submit($formulaire);
        self::assertResponseRedirects('/partenaires/etablissement');
        self::assertCount(1, $this->strapi->requetes('POST'));

        $crawler = $this->client->followRedirect();
        $this->client->submit($crawler->filter('.bz-photo form')->form());
        self::assertCount(1, $this->strapi->requetes('DELETE'));
        unlink($image);
    }

    public function testUnFichierQuiNEstPasUneImageNEstPasTransmis(): void
    {
        $this->connecter();
        $this->simulerLaFiche();

        $fichier = tempnam(sys_get_temp_dir(), 'texte').'.png';
        file_put_contents($fichier, 'pas une image');

        $crawler = $this->client->request('GET', '/partenaires/etablissement');
        $formulaire = $crawler->selectButton('Envoyer')->form();
        $formulaire['partner_photo[photo]']->upload($fichier);
        $this->client->submit($formulaire);

        self::assertSame([], $this->strapi->requetes('POST'));
        $this->client->followRedirect();
        self::assertSelectorTextContains('.bz-alerte--erreur', 'Formats acceptés');
        unlink($fichier);
    }

    public function testEnvoiDUnMessage(): void
    {
        $this->connecter();
        $this->strapi->reponses['GET /api/partner-portal/messages'] = self::json(['data' => [
            ['content' => 'Bien reçu', 'fromTeam' => true, 'createdAt' => '2026-11-27T10:00:00.000Z'],
        ]]);
        $this->strapi->reponses['POST /api/partner-portal/messages'] = self::json(['data' => []], 201);

        $crawler = $this->client->request('GET', '/partenaires/messages');
        self::assertSelectorTextContains('.bz-discussion__fil', 'Bien reçu');
        $this->client->submit($crawler->filter('form.bz-discussion__saisie')->form(['message' => 'Bonjour']));

        self::assertResponseRedirects('/partenaires/messages');
        self::assertSame(['content' => 'Bonjour'], $this->strapi->requetes('POST')[0]['body']['data']);
    }
}
