<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

use EMS\CommonBundle\Common\EMSLink;
use Twig\Extension\RuntimeExtensionInterface;

class ClientRequestRuntime implements RuntimeExtensionInterface
{
    /**
     * @var ClientRequestManager
     */
    private $manager;

    /**
     * @param ClientRequestManager $manager
     */
    public function __construct(ClientRequestManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $type
     * @param array  $body
     * @param int    $from
     * @param int    $size
     * @param array  $sourceExclude
     *
     * @return array
     */
    public function search($type, array $body, $from = 0, $size = 10, array $sourceExclude = [])
    {
        $client = $this->manager->getDefault();

        return $client->search($type, $body, $from, $size, $sourceExclude);
    }


    /**
     * @param string $input
     *
     * @return array|false|null false when multiple results
     */
    public function data(string $input)
    {
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
}