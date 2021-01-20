<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder;
use Symfony\Component\Translation\Dumper\YamlFileDumper;

final class PullHelper
{
    private TranslationBuilder $translationBuilder;
    private string $projectDir;

    public function __construct(TranslationBuilder $translationBuilder, string $projectDir)
    {
        $this->translationBuilder = $translationBuilder;
        $this->projectDir = $projectDir;
    }

    public function pullTranslations(Environment $environment): void
    {
        $dumper = new YamlFileDumper('yaml');
        $path = $this->getPath($environment, 'translations');

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $dumper->dump($messageCatalogue, ['path' => $path, 'as_tree' => true, 'inline' => 5]);
        }
    }

    private function getPath(Environment $environment, string $folder): string
    {
        return \implode(DIRECTORY_SEPARATOR, [$this->projectDir, 'local', $environment->getName(), $folder]);
    }
}
