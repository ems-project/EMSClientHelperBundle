<?php
namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;;

class TemplateLoader implements \Twig_LoaderInterface
{
    /**
     * @var ClientRequest
     */
    private $client;

    /**
     * @var array
     */
    private $config;

    const PREFIX = '@EMSCH';

    /**
     * @param ClientRequest $client
     * @param array         $config
     */
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
        return new \Twig_Source($this->getTemplate($name)['code'], $name);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated used for php < 7
     */
    public function getSource($name)
    {
        return $this->getTemplate($name)['code'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        $template = $this->getTemplate($name);

        return $template['fresh_time'] <= $time;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name): bool
    {
        return substr($name, 0, 6) === self::PREFIX;
    }

    /**
     * @param string $name
     *
     * @return array|false
     */
    private function getTemplate($name)
    {
        if (false === $match = $this->match($name)) {
            return false;
        }

        list($contentType, $searchValue, $searchTerm) = $match;

        if (!isset($this->config[$contentType])) {
            return false;
        }

        $config = $this->config[$contentType];
        $searchTerm = $searchTerm ?: $config['name'];

        return $this->search($contentType, $searchValue, $searchTerm, $config['code']);
    }

    /**
     * @param string $name
     *
     * @return array|false
     */
    private function match($name)
    {
        preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*):(?<search_val>.*)$/', $name, $matchOuuid);

        if ($matchOuuid) {
            return [$matchOuuid['content_type'], $matchOuuid['search_val'], '_id'];
        }

        preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*)\/(?<search_val>.*)$/', $name, $matchName);

        if ($matchName) {
            return [$matchName['content_type'], $matchName['search_val'], null];
        }

        return false;
    }

    /**
     * @param string $contentType
     * @param string $searchVal   ouuid, templateName
     * @param string $searchTerm  _id, key, name
     * @param string $code        code field in document
     *
     * @return array|false
     */
    private function search($contentType, $searchVal, $searchTerm, $code)
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

            if(isset($source['_finalization_datetime'])) {
                $date = new \DateTime($source['_finalization_datetime']);
            } else if(isset($source['_published_datetime'])) {
                $date = new \DateTime($source['_published_datetime']);
            } else {
                $date = new \DateTime();
            }

            return ['fresh_time' => $date->getTimestamp(), 'code' => $source[$code]];
        } catch (\Exception $e) {
            return false;
        }
    }
}