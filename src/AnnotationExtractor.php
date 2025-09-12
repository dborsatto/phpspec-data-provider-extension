<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider;

use DBorsatto\PhpSpec\DataProvider\Attribute\DataProvider;
use PhpSpec\Loader\Node\ExampleNode;
use ReflectionMethod;

use function is_iterable;

final class AnnotationExtractor
{
    /**
     * @return list<list<mixed>>|null
     */
    public function extract(ExampleNode $exampleNode): iterable|null
    {
        $functionReflection = $exampleNode->getFunctionReflection();
        if (!$functionReflection instanceof ReflectionMethod) {
            return null;
        }

        $attributes = $functionReflection->getAttributes(DataProvider::class);
        if ($attributes === []) {
            return null;
        }

        /** @var DataProvider $attribute */
        $attribute = $attributes[0]->newInstance();

        $dataProviderMethod = $attribute->methodName;

        $classReflection = $functionReflection->getDeclaringClass();
        if (!$classReflection->hasMethod($dataProviderMethod)) {
            return null;
        }

        /** @psalm-suppress MissingThrowsDocblock */
        $methodReflection = $classReflection->getMethod($dataProviderMethod);
        if (!$methodReflection->isStatic() || !$methodReflection->isPublic()) {
            return null;
        }

        $fqcn = $classReflection->getName();
        /**
         * @var list<list<mixed>>|null $providedData
         * @psalm-suppress MixedMethodCall
         */
        $providedData = $fqcn::$dataProviderMethod();

        return is_iterable($providedData) ? $providedData : null;
    }
}
