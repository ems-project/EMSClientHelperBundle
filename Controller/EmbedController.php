<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Common\EMSLink;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class EmbedController extends AbstractController
{
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(ClientRequestManager $manager)
    {
        $this->clientRequest = $manager->getDefault();
    }

    public function renderHierarchyAction(string $template, string $parent, string $field, int $depth = null, array $sourceFields = [], array $args = []): Response
    {
        $activeChild = '';
        if(isset($args['activeChild']) && $args['activeChild'] instanceof EMSLink) {
            $activeChild = $args['activeChild']->getOuuid();
        }
        $hierarchy = $this->clientRequest->getHierarchy($parent, $field, $depth, $sourceFields, $activeChild);

        return $this->render($template, [
            'translation_domain' => $this->clientRequest->getCacheKey(),
            'args' => $args,
            'hierarchy' => $hierarchy,
        ]);
    }
}