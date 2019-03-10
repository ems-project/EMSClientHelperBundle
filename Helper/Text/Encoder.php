<?php

namespace EMS\ClientHelperBundle\Helper\Text;

class Encoder
{
    public function html_encode(string $text): string
    {
        return mb_encode_numericentity(html_entity_decode($text), array(0x0, 0xffff, 0, 0xffff), 'UTF-8');
    }

    public function html_encode_pii(string $text): string
    {
        return $this->encode_phone($this->encode_email($this->encode_pii_class($text)));
    }

    /**
     *
     * Detect telephone information using the '"tel:xxx"' pattern
     * <a href="tel:02/787.45.23">02/787.45.23</a>
     */
    private function encode_phone(string $text): string
    {
        $telRegex = '/(?P<tel>"tel:.*")/i';

        return preg_replace_callback($telRegex, function ($match) {
            return $this->html_encode($match['tel']);
        }, $text);
    }

    /**
     *
     * Detect email information using the 'x@x.x' pattern
     * <a href="mailto:david.meert@smals.be">david.meert@smals.be</a>
     */
    private function encode_email(string $text): string
    {
        $emailRegex = '/(?P<email>[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}))/i';

        return preg_replace_callback($emailRegex, function ($match) {
            return $this->html_encode($match['email']);
        }, $text);
    }

    /**
     *
     * Allow to encode other pii using a class "pii"
     * <a href="tel:02/787.45.23"><span class="pii">02/787.45.23</span></a>
     *
     * The <span> element is consumed and is not kept in the end result.
     * example browser output: <a href="tel:02/787.45.23">02/787.45.23</a>
     *
     * If html tags are used inside a pii span, it will be double encoded and give unexpected results on the browser
     */
    private function encode_pii_class(string $text): string
    {
        $piiRegex = '/<span class="pii">(?P<pii>.*)<\/span>/m';

        return preg_replace_callback($piiRegex, function ($match) {
            return $this->html_encode($match['pii']);
        }, $text);
    }
}