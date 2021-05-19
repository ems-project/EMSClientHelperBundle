<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Request;

use Symfony\Component\HttpFoundation\Request;

final class RequestHelper
{
    private const PATTERN = '/%(?<parameter>(_|)[[:alnum:]]*)%/m';

    public static function replace(Request $request, string $subject): string
    {
        $result = \preg_replace_callback(self::PATTERN, function ($match) use ($request) {
            return $request->get($match['parameter'], $match[0]);
        }, $subject);

        if (!\is_string($result)) {
            throw new \RuntimeException(\sprintf('replace request failed for subject %s', $subject));
        }

        return $result;
    }
}
