<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée de façon sécurisée un compte administrateur en ligne de commande (interdit via le web).'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AdminRepository $adminRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de l\'administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair (sera haché avec l\'algorithme sécurisé)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('L\'adresse email fournie est invalide.');
            return Command::FAILURE;
        }

        if (strlen($plainPassword) < 8) {
            $io->error('Le mot de passe doit comporter au moins 8 caractères.');
            return Command::FAILURE;
        }

        $existing = $this->adminRepository->findOneByEmail($email);
        if ($existing !== null) {
            $io->warning(sprintf('Un compte administrateur avec l\'email "%s" existe déjà.', $email));
            return Command::INVALID;
        }

        $admin = new Admin();
        $admin->setEmail($email);
        $admin->setRoles(['ROLE_ADMIN']);

        // Hachage cryptographique du mot de passe (auto = bcrypt / argon2id)
        $hashedPassword = $this->passwordHasher->hashPassword($admin, $plainPassword);
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur "%s" créé avec succès !', $email));

        return Command::SUCCESS;
    }
}

