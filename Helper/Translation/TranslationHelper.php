<?php

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationHelper
{
    /**
     * @var ClientRequestManager
     */
    private $clientManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @param ClientRequestManager $clientManager
     * @param Translator           $translator
     * @param AdapterInterface     $cache
     */
    public function __construct(ClientRequestManager $clientManager, Translator $translator, AdapterInterface $cache)
    {
        $this->clientManager = $clientManager;
        $this->translator = $translator;
        $this->cache = $cache;
    }

    /**
     * @param Request $request
     */
    public function addCatalogues(Request $request)
    {
        $locale = $request->getLocale();
        $catalogue = $this->translator->getCatalogue($locale);

        foreach ($this->clientManager->all() as $clientRequest) {
            if (!$clientRequest->hasOption('translation_type')) {
                continue;
            }

            $catalogue->addCatalogue($this->createCatalogue($clientRequest, $locale));
        }
    }

    /**
     * @param ClientRequest $client
     * @param string        $locale
     *
     * @return MessageCatalogue
     */
    private function createCatalogue(ClientRequest $client, string $locale): MessageCatalogue
    {
        $lastChanged = $this->getLastChangeDate($client);
        $cacheItem = $this->cache->getItem($client->getCacheKey('translations_'.$locale));

        if (!$cacheItem->isHit() || !$this->cacheIsValid($lastChanged, $cacheItem)) {
            $messages = $this->createMessages($client, $cacheItem, $locale, $lastChanged);
        } else {
            $messages = $cacheItem->get();
        }

        $catalogue = new MessageCatalogue($locale);
        $catalogue->add($messages, $client->getCacheKey());

        return $catalogue;
    }

    /**
     * @param \DateTime $lastChanged
     * @param CacheItem $cacheItem
     *
     * @return bool
     */
    public function cacheIsValid(\DateTime $lastChanged, CacheItem $cacheItem): bool
    {
        $messages = $cacheItem->get();
        $cacheLastChanged = \DateTime::createFromFormat(DATE_ATOM, $messages['ems_last_change']);

        return $lastChanged == $cacheLastChanged;
    }

    /**
     * @param ClientRequest $client
     * @param CacheItem     $cacheItem
     * @param string        $locale
     * @param \DateTime     $lastChanged
     *
     * @return array
     */
    private function createMessages(ClientRequest $client, CacheItem $cacheItem, string $locale, \DateTime $lastChanged): array
    {
        $scroll = $client->scrollAll([
            'size' => 100,
            'type' => $client->getOption('[translation_type]'),
            'sort' => ['_doc']
        ], '5s');


        $messages = ['ems_last_change' => $lastChanged->format(DATE_ATOM)];

        foreach ($scroll as $hit) {
            if(isset($hit['_source']['label_'.$locale])){
                $messages[$hit['_source']['key']] = $hit['_source']['label_'.$locale];
            }
        }

        $cacheItem->set($messages);
        $this->cache->save($cacheItem);

        return $messages;
    }

    /**
     * @param ClientRequest $client
     *
     * @return \DateTime
     */
    private function getLastChangeDate(ClientRequest $client)
    {
        $type = $client->getOption('[translation_type]');
        $result = $client->search($type, [
            'sort' => ['_published_datetime' => ['order' => 'desc', 'missing' => '_last']],
            '_source' => '_published_datetime'
        ], 0, 1);

        if ($result['hits']['total'] > 0 && isset($result['hits']['hits']['0']['_source']['_published_datetime'])) {
            return new \DateTime($result['hits']['hits']['0']['_source']['_published_datetime']);
        }

        return new \DateTime('Wed, 09 Feb 1977 16:00:00 GMT');
    }
}