<?php

namespace EMS\ClientHelperBundle\Helper\Twig;

class Template
{
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

    public function __construct(string $template, array $config)
    {
        if (!$this->matchOuuid($template) && !$this->matchName($template)) {
            throw new TwigException(sprintf('Invalid template name: %s', $template));
        }

        if (!isset($config[$this->contentType])) {
            throw new TwigException('Missing config EMSCH_TEMPLATES');
        }

        $this->config = $config[$this->contentType];
        $this->template = $template;
    }

    public function __toString()
    {
        return $this->template;
    }

    public function getCodeField(): string
    {
        return $this->config['code'];
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getQuery(): array
    {
        if (null !== $this->name) {
            return ['term' => [$this->config['name'] => $this->name]];
        }

        return ['term' => ['_id' => $this->ouuid]];
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