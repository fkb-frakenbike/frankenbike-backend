<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:promote', description: 'Passe un utilisateur en admin (ou le repasse en user avec --demote)')]
class PromoteUserCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, "E-mail de l'utilisateur")
            ->addOption('demote', null, InputOption::VALUE_NONE, 'Repasse en user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $input->getArgument('email')]);
        if (!$user) {
            $io->error('Utilisateur introuvable.');

            return Command::FAILURE;
        }

        $role = $input->getOption('demote') ? 'user' : 'admin';
        $user->setRole($role);
        $this->em->flush();

        // L'e-mail n'est pas journalisé : on trace l'id.
        $this->logger->info('Role utilisateur modifie', ['user_id' => $user->getId(), 'role' => $role]);
        $io->success(sprintf('Role de l\'utilisateur %s: %s', $user->getId(), $role));

        return Command::SUCCESS;
    }
}
