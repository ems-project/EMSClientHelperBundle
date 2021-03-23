<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\File;

use EMS\ClientHelperBundle\Helper\Templating\Template;
use EMS\CommonBundle\Common\Standard\Json;

final class TemplatesFile
{
    /** @var array<string, string> */
    private array $templates = [];

    public static function fromJson(string $filePath): self
    {
        $templatesFile = new self();
        $content = \file_exists($filePath) ? (\file_get_contents($filePath) ?: '') : '';
        $templatesFile->templates = Json::decode($content);

        return $templatesFile;
    }

    public function addTemplate(Template $template): void
    {
        $this->templates[$template->getEmschNameId()] = $template->getEmschName();
    }

    public function getName(string $emschNameId): string
    {
        return $this->templates[$emschNameId] ?? $emschNameId;
    }

    public function toJson(): string
    {
        $templates = $this->templates;
        \asort($templates);

        return Json::encode($templates, true);
    }
}
