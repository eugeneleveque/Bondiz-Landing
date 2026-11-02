<?php

namespace App\Controller;

use App\Entity\PartnershipRequest;
use App\Form\PartnershipRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page « Devenir partenaire » : présentation et formulaire de demande.
 * Chaque demande est enregistrée (visible dans l'administration) et, si un
 * serveur d'envoi est configuré, signalée par e-mail à l'équipe.
 */
class BecomePartnerController extends AbstractController
{
    #[Route('/devenir-partenaire', name: 'app_devenir_partenaire', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(default:default_contact_email:CONTACT_EMAIL)%')] string $contactEmail,
    ): Response {
        $partnershipRequest = new PartnershipRequest();
        $form = $this->createForm(PartnershipRequestType::class, $partnershipRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Champ piège rempli : on fait comme si tout s'était bien passé.
            if ('' === trim((string) $form->get('website')->getData())) {
                $entityManager->persist($partnershipRequest);
                $entityManager->flush();
                $this->notifyTeam($partnershipRequest, $mailer, $logger, $contactEmail);
            }

            $this->addFlash('success', 'Merci ! Votre demande a bien été envoyée. Nous revenons vers vous très vite.');

            return $this->redirectToRoute('app_devenir_partenaire', ['_fragment' => 'formulaire']);
        }

        return $this->render('partners/devenir_partenaire.html.twig', [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /** L'envoi est un bonus : la demande est déjà enregistrée en base. */
    private function notifyTeam(PartnershipRequest $partnershipRequest, MailerInterface $mailer, LoggerInterface $logger, string $contactEmail): void
    {
        $email = (new Email())
            ->from($contactEmail)
            ->to($contactEmail)
            ->replyTo($partnershipRequest->getEmail())
            ->subject(\sprintf('Nouvelle demande de partenariat : %s', $partnershipRequest->getEstablishmentName()))
            ->text(implode("\n", [
                'Établissement : '.$partnershipRequest->getEstablishmentName(),
                'Activité : '.$partnershipRequest->getActivityType(),
                'Ville : '.$partnershipRequest->getCity(),
                'Contact : '.$partnershipRequest->getContactName(),
                'E-mail : '.$partnershipRequest->getEmail(),
                'Téléphone : '.($partnershipRequest->getPhone() ?: '—'),
                '',
                (string) $partnershipRequest->getMessage(),
            ]));

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $logger->warning('Demande de partenariat enregistrée, mais e-mail non envoyé.', ['exception' => $exception]);
        }
    }
}
