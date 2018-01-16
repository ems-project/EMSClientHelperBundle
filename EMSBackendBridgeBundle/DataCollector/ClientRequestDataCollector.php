<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DataCollector;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ClienttRequestDataCollector
 *
 * Collect all methods calls that are done in the service ClientRequestService
 */
class ClientRequestDataCollector extends DataCollector implements DataCollectorInterface
{
    /**
     * @var ClientRequest
     */
    protected $clientRequest;


    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = $this->clientRequest->getProfile()->getData();
    }

    public function reset()
    {
        $this->data = array();
    }

    /**
     * Returns profiled data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch.data_collector.client_request_data_collector';
    }
}