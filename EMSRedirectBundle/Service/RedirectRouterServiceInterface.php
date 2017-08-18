<?php
/**
 * Created by PhpStorm.
 * User: dameert
 * Date: 17/08/17
 * Time: 23:25
 */

namespace EMS\ClientHelperBundle\EMSRedirectBundle\Service;


interface RedirectRouterServiceInterface
{
    /**
     * getPath should transform give the path of the linkedDocument
     * The locale and source (result from elasticsearch request) are provided
     *
     * @param array $linkedDocument
     * @param string $locale
     * @param array $source
     * @return array|false
     */
    public function getPath(array $linkedDocument, $locale, array $source);
}