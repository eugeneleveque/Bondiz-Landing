<?php

namespace App\Controller;

use App\Form\ActivityType;
use App\Partner\PartnerApi;
use App\Partner\PartnerApiException;
use App\Partner\PartnerDashboard;
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

    #[Route('/activites', name: 'app_partner_activities', methods: ['GET'])]
    public function activities(#[CurrentUser] PartnerUser $partner): Response
    {
        $activities = $this->loadActivities($partner);
        if ($activities instanceof Response) {
            return $activities;
        }

        $now = new \DateTimeImmutable();
        $isPast = static function (array $activity) use ($now): bool {
            $end = $activity['endDate'] ?? $activity['startDate'];

            return null !== $end && new \DateTimeImmutable($end) < $now;
        };

        return $this->render('partner/activities.html.twig', [
            'upcoming' => array_values(array_filter($activities, static fn ($activity) => !$isPast($activity))),
            'past' => array_values(array_filter($activities, $isPast)),
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
