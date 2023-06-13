<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


    //name: 'CreateViewer',
    //description: 'Create a new viewer account',

class CreateViewerCommand extends Command
{

    private $userRepository;
    private $passwordHasher;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;

        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setName('CreateViewer')
            ->addArgument('username', InputArgument::REQUIRED, 'Viewer mail')
            ->addArgument('nom', InputArgument::REQUIRED, 'Viewer username')
            ->addArgument('password', InputArgument::REQUIRED, 'Viewer password')
            ->addOption('role', null, InputOption::VALUE_NONE, 'Set the role to "ROLE_VIEWER"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $nom=$input->getArgument('nom');
        $role = $input->getOption('role');


        $viewer = new User();
        $viewer->setEmail($username);
        $hashedPassword = $this->passwordHasher->hashPassword($viewer, $password);
        $viewer->setPassword($hashedPassword); 
        $viewer->setRoles(['ROLE_VIEWER']);
        $viewer->setCreatedAt(new \DateTimeImmutable());
        $viewer->setUsername($nom);



        $this->userRepository->save($viewer,true);

        $io->success(sprintf('Viewer account created for %s', $username));

        return Command::SUCCESS;
    }
}
