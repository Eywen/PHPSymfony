<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

#[AsCommand(
    name: 'miw:create-user',
    description: 'Creates a new user'
)]
class CreateUserCommand extends Command
{
    private const HELP_TEXT = <<< 'MARCA_FIN'
    bin/console miw:create-user <string: useremail> <string: password> [<bool: roleAdmin>]

    This command allows you to add a new user.
    ej: bin/console miw:create-user "admin1@miw.upm.es" "*MyPa44w0r6*" true

    MARCA_FIN;

    private const ARG_EMAIL = 'useremail';
    private const ARG_PASSWD = 'password';
    private const ARG_ROLE_ADMIN = 'hasRoleAdmin';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(self::HELP_TEXT)
            ->addArgument(
                name: self::ARG_EMAIL,
                mode: InputArgument::REQUIRED,
                description: 'User e-mail'
            )
            ->addArgument(
                name: self::ARG_PASSWD,
                mode: InputArgument::REQUIRED,
                description: 'User password'
            )
            ->addArgument(
                name: self::ARG_ROLE_ADMIN,
                mode: InputArgument::OPTIONAL,
                description: 'User has role Admin',
                default: false
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 if everything went fine, or an exit code
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $useremail = strval($input->getArgument(self::ARG_EMAIL));
        $roles = (
            $input->hasArgument(self::ARG_ROLE_ADMIN)
            && strcasecmp('true', strval($input->getArgument(self::ARG_ROLE_ADMIN))) === 0
        )
            ? [ 'ROLE_ADMIN' ]
            : [];
        $user = new User(
            $useremail,
            strval($input->getArgument(self::ARG_PASSWD)),
            $roles
        );
        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            strval($input->getArgument(self::ARG_PASSWD))
        );
        $user->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $output->writeln([
                'error' => $exception->getCode(),
                'message' => $exception->getMessage()
            ]);

            return Command::INVALID;
        }

        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            '',
            'User Creator:',
            '=============',
            "  Created user '$useremail' with id: " . $user->getId(),
            ''
        ]);

        return Command::SUCCESS;
    }
}
