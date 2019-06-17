<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

use EMS\ClientHelperBundle\Exception\EnvironmentNotFoundException;
use EMS\ClientHelperBundle\Helper\Search\Search;
use EMS\CommonBundle\Common\Document;
use EMS\CommonBundle\Common\EMSLink;
use Psr\Log\LoggerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ClientRequestRuntime implements RuntimeExtensionInterface
{
    /**
     * @var ClientRequestManager
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var Document[] */
    private $documents = [];

    /**
     * @param ClientRequestManager $manager
     * @param LoggerInterface $logger
     */
    public function __construct(ClientRequestManager $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
        $this->documents = [];
    }

    /**
     * @param string $type
     * @param array $body
     * @param int $from
     * @param int $size
     * @param array $sourceExclude
     *
     * @return array
     */
    public function search($type, array $body, $from = 0, $size = 10, array $sourceExclude = [])
    {
        $client = $this->manager->getDefault();

        return $client->search($type, $body, $from, $size, $sourceExclude);
    }

    /**
     * @return Search
     */
    public function searchConfig(): Search
    {
        return new Search($this->manager->getDefault());
    }

    /**
     * @param string $input
     *
     * @return array|false|null false when multiple results
     *
     * @throws EnvironmentNotFoundException
     */
    public function data(string $input)
    {
        @trigger_error(sprintf('The filter emsch_data is deprecated and should not be used anymore. use the filter emsch_get instead"'), E_USER_DEPRECATED);

        $emsLink = EMSLink::fromText($input);
        $body = [
            'query' => [
                'bool' => [
                    'must' => [['term' => ['_id' => $emsLink->getOuuid()]]],
                ]
            ]
        ];

        if ($emsLink->hasContentType()) {
            $body['query']['bool']['should'] = [
                ['term' => ['_type' => $emsLink->getContentType()]],
                ['term' => ['_contenttype' => $emsLink->getContentType()]],
                ['term' => ['contenttype' => $emsLink->getContentType()]],
            ];
        }

        $result = $this->manager->getDefault()->searchArgs(['body' => $body]);
        $total = $result['hits']['total'];

        if (1 === $total) {
            return $result['hits']['hits'];
        }

        return ($total > 1) ? false : null;
    }

    /**
     * @param string $input
     * @return Document|null
     * @throws EnvironmentNotFoundException
     */
    public function get(string $input): ?Document
    {
        $emsLink = EMSLink::fromText($input);

        if (isset($this->documents[$emsLink->__toString()])) {
            return $this->documents[$emsLink->__toString()];
        }

        $body = [
            'query' => [
                'bool' => [
                    'must' => [['term' => ['_id' => $emsLink->getOuuid()]]],
                ]
            ]
        ];

        if ($emsLink->hasContentType()) {
            $body['query']['bool']['should'] = [
                ['term' => ['_type' => $emsLink->getContentType()]],
                ['term' => ['_contenttype' => $emsLink->getContentType()]],
            ];
        }
        $result = $this->manager->getDefault()->searchArgs(['body' => $body]);

        if (0 === $result['hits']['total']) {
            return null;
        }

        if (1 !== $result['hits']['total']) {
            $this->logger->error(sprintf('emsch_get filter found %d results for the ems key %s', $result['hits']['total'], $input));
        }
        $document = new Document($emsLink->getContentType(), $emsLink->getOuuid(), $result['hits']['hits'][0]['_source']);
        $this->documents[$emsLink->__toString()] = $document;
        return $document;
    }
}
