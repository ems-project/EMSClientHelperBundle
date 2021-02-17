<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\LoginHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class LoginCommand extends AbstractLocalCommand
{
    private LoginHelper $loginHelper;

    private const ARG_USERNAME = 'username';
    private const ARG_PASSWORD = 'password';

    public function __construct(EnvironmentHelper $environmentHelper, LoginHelper $loginHelper)
    {
        parent::__construct($environmentHelper);
        $this->loginHelper = $loginHelper;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(self::ARG_USERNAME, InputArgument::OPTIONAL, 'username', null)
            ->addArgument(self::ARG_PASSWORD, InputArgument::OPTIONAL, 'password', null)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->loginHelper->setLogger($this->logger);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getArgument(self::ARG_USERNAME)) {
            $input->setArgument(self::ARG_USERNAME, $this->io->askQuestion(new Question('Username')));
        }

        if (null === $input->getArgument(self::ARG_PASSWORD)) {
            $input->setArgument(self::ARG_PASSWORD, $this->io->askHidden('Password'));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - login');

        $username = \strval($input->getArgument(self::ARG_USERNAME));
        $password = \strval($input->getArgument(self::ARG_PASSWORD));

        if (null === $profile = $this->loginHelper->login($this->environment, $username, $password)) {
            return -1;
        }

        $this->io->success(\sprintf('Welcome %s', $profile->getUsername()));

        return 1;
    }
}
