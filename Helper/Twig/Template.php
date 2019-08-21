<?php

namespace EMS\ClientHelperBundle\Helper\Twig;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

class Template
{
    /** @var ClientRequest */
    private $clientRequest;
    /** @var string */
    private $template;
    /** @var string */
    private $contentType;
    /** @var ?string */
    private $ouuid;
    /** @var ?string */
    private $name;
    /** @var array */
    private $config;

    const PREFIX = '@EMSCH';

    public function __construct(ClientRequest $clientRequest, string $template)
    {
        if (!$this->matchOuuid($template) && !$this->matchName($template)) {
            throw new TwigException(sprintf('Invalid template name: %s', $template));
        }

        if (null == $config = $clientRequest->getOption(sprintf('[templates][%s]', $this->contentType))) {
            throw new TwigException(sprintf('Missing templates config for client %s', $clientRequest->getName()));
        }

        $this->clientRequest = $clientRequest;
        $this->config = $config;
        $this->template = $template;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getCacheKey(): string
    {
        return $this->clientRequest->getCacheKey();
    }

    public function getOuuid(): ?string
    {
        return $this->ouuid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        try {
            $term = ($this->name ? [$this->config['name'] => $this->name] : ['_id' => $this->ouuid]);

            $document = $this->clientRequest->searchOne($this->contentType, [
                'query' => ['term' => $term],
            ]);

            return $document['_source'][$this->config['code']];
        } catch (\Exception $e) {
            throw new TwigException(sprintf('Template not found %s', $this->template));
        }
    }

    public function hasOuuid(): bool
    {
        return $this->ouuid !== null;
    }

    private function matchOuuid(string $name): bool
    {
        preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*):(?<ouuid>.*)$/', $name, $matchOuuid);

        if (null == $matchOuuid) {
            return false;
        }

        $this->contentType = $matchOuuid['content_type'];
        $this->ouuid = $matchOuuid['ouuid'];

        return true;
    }

    private function matchName(string $template): bool
    {
        preg_match('/^@EMSCH\/(?<content_type>[a-z][a-z0-9\-_]*)\/(?<name>.*)$/', $template, $matchName);

        if (null == $matchName) {
            return false;
        }

        $this->contentType = $matchName['content_type'];
        $this->name = $matchName['name'];

        return true;
    }
}
