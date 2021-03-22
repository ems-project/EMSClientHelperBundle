<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\CommonBundle\Contracts\CoreApi\Exception\NotAuthenticatedExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class LoginCommand extends AbstractLocalCommand
{
    private const ARG_USERNAME = 'username';
    private const ARG_PASSWORD = 'password';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(self::ARG_USERNAME, InputArgument::OPTIONAL, 'username', null)
            ->addArgument(self::ARG_PASSWORD, InputArgument::OPTIONAL, 'password', null)
        ;
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

        try {
            $coreApi = $this->localHelper->login($this->environment, $username, $password);
        } catch (NotAuthenticatedExceptionInterface $e) {
            $this->io->error('Invalid credentials!');

            return -1;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return -1;
        }

        $profile = $coreApi->user()->getProfileAuthenticated();
        $this->io->success(\sprintf('Welcome %s on %s', $profile->getUsername(), $this->environment->getBackendUrl()));

        return 1;
    }
}
