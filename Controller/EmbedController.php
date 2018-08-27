<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class EmbedController extends AbstractController
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
     * @param string $template
     * @param string $parent
     * @param string $field
     * @param $depth
     * @param array $sourceFields
     * @return Response
     */
    public function renderHierarchyAction(string $template, string $parent, string $field, $depth = null, $sourceFields = [])
    {
        $client = $this->manager->getDefault();
        $hierarchy = $client->getHierarchy($parent, $field, $depth, $sourceFields);
        return $this->render($template, [
            'hierarchy' => $hierarchy,
        ]);
    }
}