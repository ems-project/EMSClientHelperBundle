<?php

namespace EMS\ClientHelperBundle\Helper\Twig;

use EMS\ClientHelperBundle\Contracts\Templating\TemplatingInterface;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use Twig\Loader\LoaderInterface;

/**
 * Defined for each elasticms config with the option 'templates'.
 *
 * @see EMSClientHelperExtension::defineTwigLoader()
 */
class TwigLoader implements LoaderInterface, TemplatingInterface
{
    /**
     * @var ClientRequest
     */
    private $client;

    /**
     * @var array
     */
    private $config;

    public function __construct(ClientRequest $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext($name)
    {
        return new \Twig_Source($this->getTemplateCode($name), $name);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated used for php < 7
     */
    public function getSource($name)
    {
        return $this->getTemplateCode($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $this->client->getCacheKey('twig_').$name;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        $matches = $this->match($name);
        $contentTypeName = \array_shift($matches);

        if (null === $contentType = $this->client->getContentType($contentTypeName)) {
            return false;
        }

        return $contentType->isLastPublishedAfterTime($time);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name): bool
    {
        return self::PREFIX === \substr($name, 0, 6);
    }

    private function getTemplateCode(string $name): string
    {
        $match = $this->match($name);
        list($contentType, $searchValue, $searchTerm) = $match;

        if (!isset($this->config[$contentType])) {
            throw new TwigException('Missing config EMSCH_TEMPLATES');
        }

        $config = $this->config[$contentType];
        $searchTerm = $searchTerm ?: $config['name'];

        return $this->search($contentType, $searchValue, $searchTerm, $config['code']);
    }

    /**
     * @return array[string, string, string]
     */
    private function match(string $name): array
    {
        \preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*):(?<search_val>.*)$/', $name, $matchOuuid);

        if ($matchOuuid) {
            return [$matchOuuid['content_type'], $matchOuuid['search_val'], '_id'];
        }

        \preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*)\/(?<search_val>.*)$/', $name, $matchName);

        if ($matchName) {
            return [$matchName['content_type'], $matchName['search_val'], null];
        }

        throw new TwigException(\sprintf('Invalid template name: %s', $name));
    }

    private function search(string $contentTypeName, string $searchVal, string $searchTerm, string $code): string
    {
        try {
            $document = $this->client->searchOne($contentTypeName, [
                'query' => ['term' => [$searchTerm => $searchVal]],
                '_source' => [$code],
            ]);

            return $document['_source'][$code];
        } catch (\Exception $e) {
            throw new TwigException(\sprintf('Template not found %s:%s', $contentTypeName, $searchVal));
        }
    }
}
