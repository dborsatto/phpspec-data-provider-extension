<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Runner\Maintainer;

use DBorsatto\PhpSpec\DataProvider\Annotation\Parser;
use Exception;
use Override;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Runner\CollaboratorManager;
use PhpSpec\Runner\Maintainer\Maintainer;
use PhpSpec\Runner\MatcherManager;
use PhpSpec\Specification;
use ReflectionMethod;

use function array_key_exists;
use function is_array;
use function preg_match;

final class DataProviderMaintainer implements Maintainer
{
    private const string EXAMPLE_NUMBER_PATTERN = '/^(\d+)\)/';

    #[Override]
    public function supports(ExampleNode $example): bool
    {
        $valuesCollection = $this->getDataFromProvider($example);
        if ($valuesCollection === null) {
            return false;
        }

        foreach ($valuesCollection as $values) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_array($values)) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function prepare(
        ExampleNode $example,
        Specification $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators,
    ): void {
        $exampleNumber = $this->getExampleNumber($example);
        $providedData = $this->getDataFromProvider($example);
        if ($providedData === null) {
            return;
        }

        if (!array_key_exists($exampleNumber, $providedData)) {
            return;
        }

        $data = $providedData[$exampleNumber];
        $function = $example->getFunctionReflection();
        foreach ($function->getParameters() as $position => $parameter) {
            if (!array_key_exists($position, $data)) {
                continue;
            }

            /** @psalm-suppress MixedArgument */
            $collaborators->set($parameter->getName(), $data[$position]);
        }
    }

    #[Override]
    public function teardown(
        ExampleNode $example,
        Specification $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators,
    ): void {
    }

    #[Override]
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * @return list<list<mixed>>|null
     */
    private function getDataFromProvider(ExampleNode $node): ?array
    {
        $functionReflection = $node->getFunctionReflection();
        if (!$functionReflection instanceof ReflectionMethod) {
            return null;
        }

        $dataProviderMethod = Parser::getDataProvider($functionReflection);
        if (!isset($dataProviderMethod)) {
            return null;
        }

        $specification = $node->getSpecification();
        if ($specification === null) {
            return null;
        }

        $classReflection = $specification->getClassReflection();
        if (!$classReflection->hasMethod($dataProviderMethod)) {
            return null;
        }

        try {
            $subject = $classReflection->newInstance();
            /** @var list<list<mixed>> $providedData */
            $providedData = $classReflection->getMethod($dataProviderMethod)
                ->invoke($subject);
        } catch (Exception $exception) {
            return null;
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType, DocblockTypeContradiction */
        return is_array($providedData) ? $providedData : null;
    }

    private function getExampleNumber(ExampleNode $node): int
    {
        $title = $node->getTitle();
        if (preg_match(self::EXAMPLE_NUMBER_PATTERN, $title, $matches) === 0) {
            return 0;
        }

        return (int) $matches[1] - 1;
    }
}
