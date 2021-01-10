<?php

namespace EMS\ClientHelperBundle\Helper\Hashcash;

class Token
{
    /** @var string */
    private $level;

    /** @var string */
    private $csrf;

    /** @var string */
    private $random;

    const DELIMITER = '|';

    public function __construct(string $hashcash)
    {
        list($this->level, $this->csrf, $this->random) = \explode(Token::DELIMITER, $hashcash);
    }

    public function getLevel(): int
    {
        return \intval($this->level);
    }

    public function getCsrf(): string
    {
        return $this->csrf;
    }

    public function getRandom(): string
    {
        return $this->random;
    }
}
