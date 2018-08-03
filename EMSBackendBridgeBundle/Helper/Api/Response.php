<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class Response
{
    /**
     * @var array
     */
    private $data;

    /**
     * @param string $name
     * @param mixed  $data
     */
    public function addData($name, $data)
    {
        if (!is_array($data)) {
            $this->data[$name] = $data;

            return;
        }

        if (!isset($this->data[$name])) {
            $this->data[$name] = [];
        }

        $this->data[$name][] = $data;
    }

    /**
     * @return JsonResponse
     */
    public function getResponse()
    {
        return new JsonResponse($this->data);
    }

    /**
     * @param string $name
     * @param string $href
     * @param string $rel
     * @param string $type
     *
     * @return array
     */
    public static function createLink($name, $href, $rel, $type = 'GET')
    {
        return [
            $name => [
                'href' => $href,
                'rel'  => $rel,
                'type' => $type
            ]
        ];
    }
}