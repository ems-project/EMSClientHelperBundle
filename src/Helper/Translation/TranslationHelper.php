<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorBagInterface;

class TranslationHelper
{
    private ClientRequestManager $manager;
    private TranslatorBagInterface $translator;
    /** @var string[] */
    private array $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, Translator $translator, array $locales)
    {
        $this->manager = $manager;
        $this->translator = $translator;
        $this->locales = $locales;
    }

    public function addCatalogues(): void
    {
        foreach ($this->manager->all() as $clientRequest) {
            if (!$clientRequest->hasOption('translation_type')) {
                continue;
            }

            if (!$clientRequest->mustBeBind() && !$clientRequest->isBind()) {
                continue;
            }

            $messages = $this->getMessages($clientRequest);

            foreach ($this->locales as $locale) {
                if (!isset($messages[$locale])) {
                    continue;
                }

                $clientCatalog = new MessageCatalogue($locale);
                $clientCatalog->add($messages[$locale], $clientRequest->getCacheKey());

                $catalogue = $this->translator->getCatalogue($locale);
                $catalogue->addCatalogue($clientCatalog);
            }
        }
    }

    /**
     * @return array<mixed>
     */
    private function getMessages(ClientRequest $clientRequest): array
    {
        if (null === $contentType = $clientRequest->getTranslationContentType()) {
            return [];
        }

        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        $messages = $this->createMessages($clientRequest, $contentType->getName());
        $contentType->setCache($messages);
        $clientRequest->cacheContentType($contentType);

        return $messages;
    }

    /**
     * @return array<mixed>
     */
    private function createMessages(ClientRequest $clientRequest, string $type): array
    {
        $messages = [];
        $scroll = $clientRequest->scrollAll([
            'size' => 100,
            'type' => $type,
            'sort' => ['_doc'],
        ], '5s');

        foreach ($scroll as $hit) {
            foreach ($this->locales as $locale) {
                if (isset($hit['_source']['label_'.$locale])) {
                    $messages[$locale][$hit['_source']['key']] = $hit['_source']['label_'.$locale];
                }
            }
        }

        return $messages;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(): void
    {
        try {
            $this->addCatalogues();
        } catch (\Throwable $e) {
        }
    }
}
