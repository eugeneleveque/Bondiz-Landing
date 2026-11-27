<?php

namespace App\Controller\Admin;

use App\Entity\PartnershipRequest;
use App\Form\PartnerType;
use App\Partner\PartnerAdminApi;
use App\Partner\PartnerApiException;
use App\Partner\PartnerWelcomeMailer;
use App\Partner\PublicPartners;
use App\Repository\PartnershipRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Onglet « Partenaires » : les fiches vivent dans Strapi, le blog n'en garde
 * aucune copie.
 */
#[IsGranted('ROLE_ADMIN')]
class PartnerAdminController extends AbstractController
{
    /** Résultat de la création, affiché une seule fois. */
    private const IDENTIFIANTS = 'bondiz_identifiants_partenaire';

    public function __construct(
        private readonly PartnerAdminApi $api,
        private readonly PublicPartners $publicPartners,
        private readonly PartnerWelcomeMailer $welcomeMailer,
    ) {
    }

    #[AdminRoute(path: '/partenaires', name: 'partners', options: ['methods' => ['GET']])]
    public function index(Request $request): Response
    {
        $partenaires = [];
        $erreur = null;
        try {
            $partenaires = $this->api->list();
        } catch (PartnerApiException $exception) {
            $erreur = $exception->getMessage();
        }

        return $this->render('admin/partenaires.html.twig', [
            'partenaires' => $partenaires,
            'erreur' => $erreur,
            'etats' => PartnerAdminApi::STATES,
            'identifiants' => $request->getSession()->remove(self::IDENTIFIANTS),
        ]);
    }

    #[AdminRoute(path: '/partenaires/nouveau', name: 'partners_new', options: ['methods' => ['GET', 'POST']])]
    public function new(Request $request, PartnershipRequestRepository $requests, EntityManagerInterface $entityManager): Response
    {
        $demande = $request->query->has('demande') ? $requests->find($request->query->getInt('demande')) : null;

        $form = $this->createForm(PartnerType::class, $this->depuisDemande($demande));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $partenaire = $this->api->create($form->getData());
            } catch (PartnerApiException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }

            if (isset($partenaire)) {
                if ($demande) {
                    $demande->setHandled(true);
                    $entityManager->flush();
                }
                $this->publicPartners->forget();
                $envoye = $this->welcomeMailer->send($partenaire['name'], $partenaire['email'], $partenaire['password']);
                $request->getSession()->set(self::IDENTIFIANTS, [
                    'name' => $partenaire['name'],
                    'email' => $partenaire['email'],
                    // Gardé seulement si l'e-mail n'est pas parti.
                    'password' => $envoye ? null : $partenaire['password'],
                ]);

                return $this->redirectToRoute('admin_partners');
            }
        }

        return $this->render('admin/partenaire_nouveau.html.twig', [
            'form' => $form,
            'demande' => $demande,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[AdminRoute(path: '/partenaires/{documentId}/etat', name: 'partners_state', options: ['methods' => ['POST']])]
    public function state(string $documentId, Request $request): Response
    {
        $etat = $request->request->getString('etat');

        if (!$this->isCsrfTokenValid('partenaire-etat', $request->request->getString('_token'))) {
            $this->addFlash('danger', 'La page a expiré, réessayez.');
        } elseif (isset(PartnerAdminApi::STATES[$etat])) {
            try {
                $this->api->setState($documentId, $etat);
                $this->publicPartners->forget();
                $this->addFlash('success', 'État mis à jour.');
            } catch (PartnerApiException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->redirectToRoute('admin_partners');
    }

    /** Boîte de réception : les partenaires qui ont des messages. */
    #[AdminRoute(path: '/partenaires/messages', name: 'partner_messages', options: ['methods' => ['GET']])]
    public function conversations(): Response
    {
        $conversations = [];
        $erreur = null;
        try {
            $conversations = $this->api->conversations();
        } catch (PartnerApiException $exception) {
            $erreur = $exception->getMessage();
        }

        return $this->render('admin/messagerie.html.twig', [
            'conversations' => $conversations,
            'erreur' => $erreur,
        ]);
    }

    #[AdminRoute(path: '/partenaires/{documentId}/messages', name: 'partner_thread', options: ['methods' => ['GET', 'POST']])]
    public function thread(string $documentId, Request $request): Response
    {
        $brouillon = '';

        if ($request->isMethod('POST')) {
            $brouillon = trim($request->request->getString('message'));

            if (!$this->isCsrfTokenValid('partenaire-message', $request->request->getString('_token'))) {
                $this->addFlash('danger', 'La page a expiré, réessayez.');
            } elseif ('' === $brouillon) {
                $this->addFlash('danger', 'Le message est vide.');
            } else {
                try {
                    $this->api->reply($documentId, $brouillon);

                    return $this->redirectToRoute('admin_partner_thread', ['documentId' => $documentId]);
                } catch (PartnerApiException $exception) {
                    $this->addFlash('danger', $exception->getMessage());
                }
            }
        }

        try {
            $fil = $this->api->thread($documentId);
        } catch (PartnerApiException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('admin_partner_messages');
        }

        return $this->render('admin/conversation.html.twig', [
            'partenaire' => $fil['partner'],
            'messages' => $fil['messages'],
            'brouillon' => $brouillon,
        ]);
    }

    /** @return array<string, mixed> */
    private function depuisDemande(?PartnershipRequest $demande): array
    {
        if (!$demande) {
            return ['states' => 'active'];
        }

        return [
            'name' => $demande->getEstablishmentName(),
            'email' => $demande->getEmail(),
            'city' => $demande->getCity(),
            'contactLastName' => $demande->getContactName(),
            'states' => 'active',
        ];
    }
}
