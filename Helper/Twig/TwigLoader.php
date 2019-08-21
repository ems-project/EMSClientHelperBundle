<?php
namespace EMS\ClientHelperBundle\Helper\Twig;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Defined for each elasticms config with the option 'templates'
 * @see EMSClientHelperExtension::defineTwigLoader()
 */
class TwigLoader implements LoaderInterface
{
    /** @var ClientRequest */
    private $client;
    /** @var TemplateManager */
    private $templateManager;

    public function __construct(ClientRequest $client, TemplateManager $templateManager)
    {
        $this->client = $client;
        $this->templateManager = $templateManager;
    }

    public function getSourceContext($name)
    {
        $template = new Template($this->client, $name);

        return new Source($this->templateManager->getCode($template), $name);
    }

    public function getCacheKey($name)
    {
        return $this->client->getCacheKey('twig_') . $name;
    }

    public function isFresh($name, $time)
    {
        $template = new Template($this->client, $name);

        if ($this->templateManager->isDownloaded($template)) {
            return false;
        }

        return ($this->client->getLastChangeDate($template->getContentType())->getTimestamp() <= $time);
    }

    public function exists($name): bool
    {
        return substr($name, 0, 6) === Template::PREFIX;
    }
}
