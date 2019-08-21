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
    /** @var array */
    private $config;

    public function __construct(ClientRequest $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function getSourceContext($name)
    {
        $template = new Template($name, $this->config);
        $document = $this->getDocument($template);

        return new Source($document['_source'][$template->getCodeField()], $name);
    }

    public function getCacheKey($name)
    {
        return $this->client->getCacheKey('twig_') . $name;
    }

    public function isFresh($name, $time)
    {
        $template = new Template($name, $this->config);

        return ($this->client->getLastChangeDate($template->getContentType())->getTimestamp() <= $time);
    }

    public function exists($name): bool
    {
        return substr($name, 0, 6) === Template::PREFIX;
    }

    private function getDocument(Template $template): array
    {
        try {
            $document = $this->client->searchOne($template->getContentType(), [
                'query' => $template->getQuery(),
            ]);

            return $document;
        } catch (\Exception $e) {
            throw new TwigException(sprintf('Template not found %s', $template));
        }
    }
}
