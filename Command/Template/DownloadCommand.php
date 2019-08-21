<?php

namespace EMS\ClientHelperBundle\Command\Template;

use EMS\ClientHelperBundle\Helper\Twig\TemplateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadCommand extends Command
{
    /** @var TemplateManager */
    private $templateManager;

    protected static $defaultName = 'emsch:template:download';

    public function __construct(TemplateManager $templateManager)
    {
        parent::__construct();
        $this->templateManager = $templateManager;
    }

    protected function configure()
    {
        $this->setDescription('Download templates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Template command');

        $logger = new ConsoleLogger($output);

        $progressBar = $style->createProgressBar();
        $progressBar->start();

        foreach ($this->templateManager->download() as $template) {
            $logger->info($template);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
