<?php

// src/Command/CreateAdminUserCommand.php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminUserCommand extends Command
{
    protected static $defaultName = 'app:create-admin-user';

    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Crée un nouvel utilisateur administrateur.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Créer un nouvel utilisateur
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        // Ajouter les nouveaux attributs
        $user->setNom('Admin');
        $user->setPrenom('User');
        $user->setAdresse('123 Rue Admin');
        $user->setNumTel('0123456789');
        $user->setPhoto('admin.jpg'); // Vous pouvez laisser ce champ vide ou définir une valeur par défaut

        // Enregistrer l'utilisateur dans la base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Utilisateur admin créé avec succès !');

        return Command::SUCCESS;
    }
}