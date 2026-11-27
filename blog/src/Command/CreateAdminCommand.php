<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Seul moyen de créer un compte : le site n'a pas d'inscription publique.
 * Relancée sur un e-mail existant, la commande change son mot de passe.
 */
#[AsCommand(name: 'app:admin', description: 'Crée un administrateur ou change son mot de passe')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail de connexion');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresse e-mail invalide.');

            return Command::INVALID;
        }

        // Saisi sans affichage, pour ne pas rester dans l'historique du terminal.
        $password = (string) $io->askHidden('Mot de passe (12 caractères minimum)');

        if (mb_strlen($password) < 12) {
            $io->error('Mot de passe trop court : rien n’a été modifié.');

            return Command::INVALID;
        }

        $user = $this->users->findOneBy(['email' => $email])
            ?? (new User())->setEmail($email)->setPseudo('Admin');

        $user->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(\sprintf('Administrateur prêt : %s', $email));

        return Command::SUCCESS;
    }
}
