<?php
namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\EnvironmentNotFoundException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\RequestService;
use function json_encode;
use function sizeof;
use Twig_Error_Loader;
use Twig_Loader_Filesystem;
use Twig_LoaderInterface;
use Twig_Source;

class Twig_Loader_Elasticsearch implements Twig_LoaderInterface
{
    /**
     * @var ClientRequest
     */
    private $client;

    /**
     * @var string
     */
    private $project;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $type;

    /**
     * @param ClientRequest $client
     * @param string $project
     * @param string $prefix
     * @param string $type
     */
    public function __construct(ClientRequest $client, $project, $prefix, $type)
    {
        $this->client = $client;
        $this->project = $project;
        $this->prefix = $prefix;
        $this->type = $type;
    }
    /**
     * @param string $name
     * @return Twig_Source|void
     */
    public function getSourceContext($name)
    {
        $template = $this->findTemplate($name)['body'];

        return new Twig_Source($template, $name);
    }

    /**
     * @param string $name
     * @return string|void
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * @param string $name
     * @param int $time
     * @return bool|void
     */
    public function isFresh($name, $time)
    {
        $template = $this->findTemplate($name);
        $date = new \DateTime();
        if( isset($template['_published_datetime']) )
        {
            $date = new \DateTime($template['_published_datetime']);
        }
        if( isset($template['_finalization_datetime']) )
        {
            $date = new \DateTime($template['_finalization_datetime']);
        }
        return  $date->getTimestamp() <= $time;
    }

    private function findTemplate($name)
    {

        try {
            preg_match('/^@EMSCH\/([a-z][a-z0-9\-_]*):(.*)$/', $name, $matches);
            if(count($matches) ===3) {
                $result = $this->client->get($matches[1], $matches[2]);
                return $result['_source'];
            }


            preg_match('/^@EMSCH\/([a-z][a-z0-9\-_]*)\/(.*)$/', $name, $matches);
            if(count($matches) ===3) {
                $result = $this->client->searchOne($matches[1], [
                    'query' => [
                        'term' => [
                            'key' => $matches[2]
                        ]
                    ],
                    '_source' => ['body', '_published_datetime', '_finalization_datetime'],
                ]);
                return $result['_source'];
            }


        }
        catch (EnvironmentNotFoundException $e) {
        }
        catch (SingleResultException $e) {
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool|void
     */
    public function exists($name)
    {
        return false !== $this->findTemplate($name);
    }


}