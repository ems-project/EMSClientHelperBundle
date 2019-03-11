<?php

namespace EMS\ClientHelperBundle\Tests\Helper\Text;


use EMS\ClientHelperBundle\Helper\Text\Encoder;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{
    /** @var Encoder */
    private $encoder;

    protected function setUp()
    {
        $this->encoder = new Encoder();
        parent::setUp();
    }

    /**
     * format: [text, &#ascii;]
     */
    public function htmlProvider(): array
    {
        return [
            ['example', '&#101;&#120;&#97;&#109;&#112;&#108;&#101;'],
            ['@', '&#64;'],
            ['.', '&#46;'],
            ['example@example.com', '&#101;&#120;&#97;&#109;&#112;&#108;&#101;&#64;&#101;&#120;&#97;&#109;&#112;&#108;&#101;&#46;&#99;&#111;&#109;'],
            ['é', '&#233;'],
            ['<', '&#60;'],
        ];
    }

    /**
     * @dataProvider htmlProvider
     */
    public function testHtml_encode(string $text, string $expected)
    {
        self::assertSame($expected, $this->encoder->html_encode($text));
    }

    /**
     * format: [text, &#ascii;]
     */
    public function piiProvider(): array
    {
        $email = '&#101;&#120;&#97;&#109;&#112;&#108;&#101;&#64;&#101;&#120;&#97;&#109;&#112;&#108;&#101;&#46;&#99;&#111;&#109;';
        $example = '&#101;&#120;&#97;&#109;&#112;&#108;&#101;'; //example, no <span> tag included!

        return [
            ['example', 'example'],
            ['@', '@'],
            ['.', '.'],
            ['example@example.com', $email],
            ['é', 'é'],
            ['<', '<'],
            ['mailto:example@example.com', sprintf('mailto:%s', $email)],
            ['"tel:02/345.67.89"', '&#34;&#116;&#101;&#108;&#58;&#48;&#50;&#47;&#51;&#52;&#53;&#46;&#54;&#55;&#46;&#56;&#57;&#34;'],
            ['<span class="pii">example</span>', $example],
        ];
    }

    /**
     * @dataProvider piiProvider
     */
    public function testHtml_encode_pii(string $text, string $expected)
    {
        self::assertSame($expected, $this->encoder->html_encode_pii($text));
    }
}
