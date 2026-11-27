<?php

namespace App\Controller;

use App\Form\ActivityType;
use App\Form\EstablishmentType;
use App\Form\PartnerPhotoType;
use App\Partner\PartnerApi;
use App\Partner\PartnerApiException;
use App\Partner\PartnerDashboard;
use App\Partner\PublicPartners;
use App\Partner\StrapiMedia;
use App\Security\PartnerUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Espace partenaire : les établissements partenaires se connectent avec les
 * identifiants fournis par l'équipe Bondiz et publient leurs activités, qui
 * apparaissent ensuite dans l'application.
 */
#[Route('/partenaires')]
class PartnerController extends AbstractController
{
    public function __construct(private readonly PartnerApi $partnerApi)
    {
    }

    #[Route('/connexion', name: 'app_partner_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof PartnerUser) {
            return $this->redirectToRoute('app_partner_dashboard');
        }

        return $this->render('partner/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_partner_logout')]
    public function logout(): void
    {
        throw new \LogicException('Intercepté par le pare-feu "partner".');
    }

    #[Route('', name: 'app_partner_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, #[CurrentUser] PartnerUser $partner, PartnerDashboard $partnerDashboard): Response
    {
        $activities = $this->loadActivities($partner);
        if ($activities instanceof Response) {
            return $activities;
        }

        $semaines = $request->query->getInt('semaines', 4);
        if (!\in_array($semaines, PartnerDashboard::PERIODES, true)) {
            $semaines = 4;
        }

        return $this->render('partner/dashboard.html.twig', [
            'tableau' => $partnerDashboard->build($activities, $semaines),
            'semaines' => $semaines,
        ]);
    }

    public const FILTRES_ACTIVITES = ['toutes', 'a-venir', 'passees'];

    #[Route('/activites', name: 'app_partner_activities', methods: ['GET'])]
    public function activities(Request $request, #[CurrentUser] PartnerUser $partner, PartnerDashboard $partnerDashboard): Response
    {
        $activities = $this->loadActivities($partner);
        if ($activities instanceof Response) {
            return $activities;
        }

        $filtre = $request->query->getString('filtre', 'toutes');
        if (!\in_array($filtre, self::FILTRES_ACTIVITES, true)) {
            $filtre = 'toutes';
        }

        return $this->render('partner/activities.html.twig', [
            'tableau' => $partnerDashboard->build($activities),
            'liste' => $partnerDashboard->liste($activities),
            'filtre' => $filtre,
        ]);
    }

    #[Route('/statistiques', name: 'app_partner_statistics', methods: ['GET'])]
    public function statistics(#[CurrentUser] PartnerUser $partner, PartnerDashboard $partnerDashboard): Response
    {
        $activities = $this->loadActivities($partner);
        if ($activities instanceof Response) {
            return $activities;
        }

        return $this->render('partner/statistics.html.twig', [
            'tableau' => $partnerDashboard->build($activities),
        ]);
    }

    #[Route('/etablissement', name: 'app_partner_establishment', methods: ['GET'])]
    public function establishment(#[CurrentUser] PartnerUser $partner, PartnerDashboard $partnerDashboard, StrapiMedia $media): Response
    {
        $activities = $this->loadActivities($partner);
        if ($activities instanceof Response) {
            return $activities;
        }

        try {
            $profile = $this->partnerApi->getProfile($partner);
        } catch (PartnerApiException $exception) {
            if ($response = $this->handleApiError($exception)) {
                return $response;
            }
            $profile = null;
        }

        $photos = [];
        foreach ($profile['photos'] ?? [] as $photo) {
            $photos[] = ['id' => $photo['id'], 'url' => $media->url($photo['url'])];
        }

        return $this->render('partner/establishment.html.twig', [
            'tableau' => $partnerDashboard->build($activities),
            'fiche' => $profile,
            'photos' => $photos,
            'jours' => EstablishmentType::JOURS,
            'formulairePhoto' => $this->createForm(PartnerPhotoType::class, null, [
                'action' => $this->generateUrl('app_partner_photo_add'),
            ])->createView(),
        ]);
    }

    #[Route('/etablissement/modifier', name: 'app_partner_establishment_edit', methods: ['GET', 'POST'])]
    public function editEstablishment(Request $request, #[CurrentUser] PartnerUser $partner, PublicPartners $publicPartners): Response
    {
        try {
            $profile = $this->partnerApi->getProfile($partner);
        } catch (PartnerApiException $exception) {
            return $this->handleApiError($exception) ?? $this->redirectToRoute('app_partner_establishment');
        }

        $form = $this->createForm(EstablishmentType::class, EstablishmentType::fromApiData($profile));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->partnerApi->updateProfile($partner, EstablishmentType::toApiData($form->getData()));
                $publicPartners->forget();
                $this->addFlash('success', 'Votre fiche a été mise à jour.');

                return $this->redirectToRoute('app_partner_establishment');
            } catch (PartnerApiException $exception) {
                if ($response = $this->handleApiError($exception, $form)) {
                    return $response;
                }
            }
        }

        return $this->render('partner/establishment_form.html.twig', [
            'form' => $form,
            'jours' => EstablishmentType::JOURS,
        ]);
    }

    #[Route('/etablissement/photos', name: 'app_partner_photo_add', methods: ['POST'])]
    public function addPhoto(Request $request, #[CurrentUser] PartnerUser $partner, PublicPartners $publicPartners): Response
    {
        $form = $this->createForm(PartnerPhotoType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('photo')->getData();
            try {
                $this->partnerApi->addPhoto($partner, $file->getPathname(), $file->getClientOriginalName());
                $publicPartners->forget();
                $this->addFlash('success', 'Photo ajoutée.');
            } catch (PartnerApiException $exception) {
                if ($response = $this->handleApiError($exception)) {
                    return $response;
                }
            }
        } else {
            $erreur = $form->getErrors(true)->current();
            // Au-delà de la limite de PHP, le formulaire arrive vide.
            $this->addFlash('error', $erreur ? $erreur->getMessage() : 'La photo n’a pas pu être envoyée (5 Mo maximum).');
        }

        return $this->redirectToRoute('app_partner_establishment');
    }

    #[Route('/etablissement/photos/{id<\d+>}/supprimer', name: 'app_partner_photo_delete', methods: ['POST'])]
    public function deletePhoto(Request $request, int $id, #[CurrentUser] PartnerUser $partner, PublicPartners $publicPartners): Response
    {
        if (!$this->isCsrfTokenValid('delete-photo-'.$id, $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'La suppression a expiré, réessayez.');

            return $this->redirectToRoute('app_partner_establishment');
        }

        try {
            $this->partnerApi->deletePhoto($partner, $id);
            $publicPartners->forget();
            $this->addFlash('success', 'Photo supprimée.');
        } catch (PartnerApiException $exception) {
            if ($response = $this->handleApiError($exception)) {
                return $response;
            }
        }

        return $this->redirectToRoute('app_partner_establishment');
    }

    /** Messagerie avec l'équipe Bondiz : un seul fil par partenaire. */
    #[Route('/messages', name: 'app_partner_messages', methods: ['GET', 'POST'])]
    public function messages(Request $request, #[CurrentUser] PartnerUser $partner): Response
    {
        $brouillon = '';

        if ($request->isMethod('POST')) {
            $brouillon = trim($request->request->getString('message'));

            if (!$this->isCsrfTokenValid('partner-message', $request->request->getString('_token'))) {
                $this->addFlash('error', 'La page a expiré, réessayez.');
            } elseif ('' === $brouillon) {
                $this->addFlash('error', 'Le message est vide.');
            } else {
                try {
                    $this->partnerApi->sendMessage($partner, $brouillon);

                    return $this->redirectToRoute('app_partner_messages');
                } catch (PartnerApiException $exception) {
                    if ($response = $this->handleApiError($exception)) {
                        return $response;
                    }
                }
            }
        }

        $messages = [];
        try {
            $messages = $this->partnerApi->listMessages($partner);
        } catch (PartnerApiException $exception) {
            if ($response = $this->handleApiError($exception)) {
                return $response;
            }
        }

        return $this->render('partner/messages.html.twig', [
            'messages' => $messages,
            'brouillon' => $brouillon,
        ]);
    }

    /**
     * Activités du partenaire ; en cas d'erreur, message flash et liste vide.
     * Session Strapi expirée : redirection vers la déconnexion.
     *
     * @return list<array<string, mixed>>|Response
     */
    private function loadActivities(PartnerUser $partner): array|Response
    {
        try {
            return $this->partnerApi->listActivities($partner);
        } catch (PartnerApiException $exception) {
            if ($exception->isUnauthorized()) {
                return $this->redirectToRoute('app_partner_logout');
            }
            $this->addFlash('error', $exception->getMessage());

            return [];
        }
    }

    #[Route('/activites/nouvelle', name: 'app_partner_activity_new', methods: ['GET', 'POST'])]
    public function new(Request $request, #[CurrentUser] PartnerUser $partner): Response
    {
        $form = $this->createForm(ActivityType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $activity = $this->partnerApi->createActivity($partner, ActivityType::toApiData($form->getData()));
                $this->addFlash('success', \sprintf('« %s » est en ligne dans l’application.', $activity['title']));

                return $this->redirectToRoute('app_partner_activities');
            } catch (PartnerApiException $exception) {
                if ($response = $this->handleApiError($exception, $form)) {
                    return $response;
                }
            }
        }

        return $this->render('partner/activity_form.html.twig', [
            'form' => $form,
            'activity' => null,
        ]);
    }

    #[Route('/activites/{id}/modifier', name: 'app_partner_activity_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id, #[CurrentUser] PartnerUser $partner): Response
    {
        try {
            $activity = $this->partnerApi->getActivity($partner, $id);
        } catch (PartnerApiException $exception) {
            return $this->handleApiError($exception) ?? $this->redirectToRoute('app_partner_activities');
        }

        $form = $this->createForm(ActivityType::class, ActivityType::fromApiData($activity));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $activity = $this->partnerApi->updateActivity($partner, $id, ActivityType::toApiData($form->getData()));
                $this->addFlash('success', \sprintf('« %s » a été mise à jour.', $activity['title']));

                return $this->redirectToRoute('app_partner_activities');
            } catch (PartnerApiException $exception) {
                if ($response = $this->handleApiError($exception, $form)) {
                    return $response;
                }
            }
        }

        return $this->render('partner/activity_form.html.twig', [
            'form' => $form,
            'activity' => $activity,
        ]);
    }

    #[Route('/activites/{id}/supprimer', name: 'app_partner_activity_delete', methods: ['POST'])]
    public function delete(Request $request, string $id, #[CurrentUser] PartnerUser $partner): Response
    {
        if (!$this->isCsrfTokenValid('delete-activity-'.$id, $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'La suppression a expiré, réessayez.');

            return $this->redirectToRoute('app_partner_activities');
        }

        try {
            $this->partnerApi->deleteActivity($partner, $id);
            $this->addFlash('success', 'L’activité a été supprimée.');
        } catch (PartnerApiException $exception) {
            if ($response = $this->handleApiError($exception)) {
                return $response;
            }
        }

        return $this->redirectToRoute('app_partner_activities');
    }

    /**
     * Session Strapi expirée ou compte désactivé : déconnexion. Sinon, le
     * message est affiché sur le formulaire, ou en message flash.
     */
    private function handleApiError(PartnerApiException $exception, ?FormInterface $form = null): ?Response
    {
        if ($exception->isUnauthorized()) {
            return $this->redirectToRoute('app_partner_logout');
        }

        if (null !== $form) {
            $form->addError(new FormError($exception->getMessage()));
        } else {
            $this->addFlash('error', $exception->getMessage());
        }

        return null;
    }
}
