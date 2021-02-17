<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;

final class PushHelper
{
    private LocalHelper $localHelper;
    private LoggerInterface $logger;

    public function __construct(LocalHelper $localHelper, LoggerInterface $logger)
    {
        $this->localHelper = $localHelper;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function push(Environment $environment): void
    {
        $localEnvironment = $this->localHelper->local($environment);
        $localEnvironment->setLogger($this->logger);

       // $translations = $this->changeListHelper->translations($localEnvironment);

//        $translations = $this->createChangeListTranslations($localEnvironment);



        return;


//        $this->pullTranslations($localEnvironment);
//        $templatesFile = $this->pullTemplates($localEnvironment);
//        $this->pullRoutes($localEnvironment, $templatesFile);
    }

//    private function createChangeListTranslations(LocalEnvironment $localEnvironment): ChangeList
//    {
//        $changeList = new ChangeList();
//
//        $documents = $this->translationBuilder->getDocuments($localEnvironment->getEnvironment());
//        foreach ($documents as $doc) {
//            $changeList->addOrigin($doc['_source']['key'], $doc['_id'], $doc['_source']);
//        }
//
//        $localTranslations = [];
//        foreach ($localEnvironment->getTranslationFiles() as $file) {
//            foreach ($file->toArray() as $key => $value) {
//                $localTranslations[$key]['label_' . $file->locale] = $value;
//            }
//        }
//
//        foreach ($localTranslations as $key => $source) {
//            $changeList->addLocal($key, $source);
//        }
//
//        return $changeList;
//    }

}
