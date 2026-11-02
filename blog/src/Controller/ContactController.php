<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactMessageType;
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
 * Page « Nous contacter ». Chaque message est enregistré (visible dans
 * l'administration) et, si un serveur d'envoi est configuré, transmis par
 * e-mail à l'équipe.
 */
class ContactController extends AbstractController
{
    #[Route('/nous-contacter', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(default:default_contact_email:CONTACT_EMAIL)%')] string $contactEmail,
    ): Response {
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $contactMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Champ piège rempli : on fait comme si tout s'était bien passé.
            if ('' === trim((string) $form->get('website')->getData())) {
                $entityManager->persist($contactMessage);
                $entityManager->flush();
                $this->notifyTeam($contactMessage, $mailer, $logger, $contactEmail);
            }

            $this->addFlash('success', 'Merci ! Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');

            return $this->redirectToRoute('app_contact', ['_fragment' => 'formulaire']);
        }

        return $this->render('contact/nous_contacter.html.twig', [
            'form' => $form,
            'contactEmail' => $contactEmail,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /** L'envoi est un bonus : le message est déjà enregistré en base. */
    private function notifyTeam(ContactMessage $contactMessage, MailerInterface $mailer, LoggerInterface $logger, string $contactEmail): void
    {
        $email = (new Email())
            ->from($contactEmail)
            ->to($contactEmail)
            ->replyTo($contactMessage->getEmail())
            ->subject('[Contact] '.$contactMessage->getSubject())
            ->text(\sprintf(
                "De : %s %s <%s>\n\n%s",
                $contactMessage->getFirstName(),
                $contactMessage->getLastName(),
                $contactMessage->getEmail(),
                $contactMessage->getMessage(),
            ));

        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $logger->warning('Message de contact enregistré, mais e-mail non envoyé.', ['exception' => $exception]);
        }
    }
}
