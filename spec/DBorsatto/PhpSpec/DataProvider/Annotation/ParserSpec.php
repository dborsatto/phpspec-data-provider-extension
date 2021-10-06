<?php

declare(strict_types=1);

namespace spec\DBorsatto\PhpSpec\DataProvider\Annotation;

use PhpSpec\ObjectBehavior;
use ReflectionMethod;

class ParserSpec extends ObjectBehavior
{
    public function it_returns_data_provider_method_name(ReflectionMethod $reflectionMethod): void
    {
        $annotation = <<<'ANNOTATION'
            /**
             * @dataProvider positiveExample
             */
            ANNOTATION;

        $reflectionMethod->getDocComment()
            ->willReturn($annotation);

        $this::getDataProvider($reflectionMethod)
            ->shouldReturn('positiveExample');
    }
}
