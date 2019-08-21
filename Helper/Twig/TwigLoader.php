<?php
namespace EMS\ClientHelperBundle\Helper\Twig;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

/**
 * Defined for each elasticms config with the option 'templates'
 * @see EMSClientHelperExtension::defineTwigLoader()
 */
class TwigLoader implements \Twig_LoaderInterface
{
    /** @var ClientRequest */
    private $client;
    /** @var array */
    private $config;

    const PREFIX = '@EMSCH';

    public function __construct(ClientRequest $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function getSourceContext($name)
    {
        return new \Twig_Source($this->getTemplate($name)['code'], $name);
    }

    /**
     * @deprecated used for php < 7
     */
    public function getSource($name)
    {
        return $this->getTemplate($name)['code'];
    }

    public function getCacheKey($name)
    {
        return $this->client->getCacheKey('twig_') . $name;
    }

    public function isFresh($name, $time)
    {
        $matches = $this->match($name);
        $contentType = \array_shift($matches);
        return ($this->client->getLastChangeDate($contentType)->getTimestamp() <= $time);
    }

    public function exists($name): bool
    {
        return substr($name, 0, 6) === self::PREFIX;
    }

    private function getTemplate(string $name): array
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

    private function match(string $name): array
    {
        preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*):(?<search_val>.*)$/', $name, $matchOuuid);

        if ($matchOuuid) {
            return [$matchOuuid['content_type'], $matchOuuid['search_val'], '_id'];
        }

        preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*)\/(?<search_val>.*)$/', $name, $matchName);

        if ($matchName) {
            return [$matchName['content_type'], $matchName['search_val'], null];
        }

        throw new TwigException(sprintf('Invalid template name: %s', $name));
    }

    private function search(string $contentType, string $searchVal, string $searchTerm, string $code): array
    {
        try {
            $document = $this->client->searchOne($contentType, [
                'query' => [
                    'term' => [
                        $searchTerm => $searchVal
                    ]
                ],
                '_source' => [$code, '_published_datetime', '_finalization_datetime'],
            ]);

            $source = $document['_source'];
            $date = $this->client->getLastChangeDate($contentType);

            return ['fresh_time' => $date->getTimestamp(), 'code' => $source[$code]];
        } catch (\Exception $e) {
            throw new TwigException(sprintf('Template not found %s:%s', $contentType, $searchVal));
        }
    }
}
