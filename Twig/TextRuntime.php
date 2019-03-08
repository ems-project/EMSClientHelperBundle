<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Text\Encoder;
use Twig\Extension\RuntimeExtensionInterface;

class TextRuntime implements RuntimeExtensionInterface
{
    /** @var Encoder */
    private $encoder;

    public function __construct(Encoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public function html_encode(string $text)
    {
        return $this->encoder->html_encode($text);
    }

    public function html_encode_pii(string $text)
    {
        return $this->encoder->html_encode_pii($text);
    }
}