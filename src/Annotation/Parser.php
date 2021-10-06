<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Annotation;

use ReflectionMethod;

class Parser
{
    private const DATA_PROVIDER_PATTERN = '/@dataProvider ([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';

    public static function getDataProvider(ReflectionMethod $reflection): ?string
    {
        $docComment = $reflection->getDocComment();
        if ($docComment === false) {
            return null;
        }

        $matches = [];
        if (preg_match(self::DATA_PROVIDER_PATTERN, $docComment, $matches) === 0) {
            return null;
        }

        return $matches[1];
    }
}
