<?php

namespace EMS\ClientHelperBundle\Command\Template;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class DownloadCommand extends Command
{
    /** @var ClientRequestManager */
    private $clientRequestManager;
    /** @var string */
    private $templatesDir;

    protected static $defaultName = 'emsch:template:download';

    /**
     * @todo better injection of template dir
     */
    public function __construct(ClientRequestManager $clientRequestManager, string $projectDir)
    {
        parent::__construct();
        $this->clientRequestManager = $clientRequestManager;
        $this->templatesDir = $projectDir . '/templates';
    }

    protected function configure()
    {
        $this
            ->setDescription('Download templates');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('Template command');

        $clientRequest = $this->clientRequestManager->getDefault();

        $dir = $this->templatesDir . '/' . $clientRequest->getCacheKey();
        $templates = $clientRequest->getOption('[templates]');

        $filesystem = new Filesystem();
        $filesystem->mkdir($dir);

        foreach ($templates as $contentType => $mapping) {
            foreach ($clientRequest->scrollAll(['body' => ['query' => ['term' => ['_contenttype' => $contentType ]]] ]) as $hit) {
                list($fieldName, $fieldTwig) = array_values($mapping);
                $source = $hit['_source'];
                $filesystem->dumpFile($dir . '/' . $contentType . '/' . $source[$fieldName], $source[$fieldTwig]);
            }
        }
    }
}
