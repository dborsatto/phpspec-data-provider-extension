<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Runner\Maintainer;

use DBorsatto\PhpSpec\DataProvider\Annotation\Parser;
use Exception;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Runner\CollaboratorManager;
use PhpSpec\Runner\Maintainer\Maintainer;
use PhpSpec\Runner\MatcherManager;
use PhpSpec\Specification;
use ReflectionMethod;
use function array_key_exists;
use function is_array;

class DataProviderMaintainer implements Maintainer
{
    public const EXAMPLE_NUMBER_PATTERN = '/^(\d+)\)/';

    public function supports(ExampleNode $example): bool
    {
        $valuesCollection = $this->getDataFromProvider($example);
        if ($valuesCollection === null) {
            return false;
        }

        foreach ($valuesCollection as $values) {
            if (!is_array($values)) {
                return false;
            }
        }

        return true;
    }

    public function prepare(
        ExampleNode $example,
        Specification $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators
    ): void {
        $exampleNum = $this->getExampleNumber($example);
        $providedData = $this->getDataFromProvider($example);
        if ($providedData === null) {
            return;
        }

        if (!array_key_exists($exampleNum, $providedData)) {
            return;
        }

        $data = $providedData[$exampleNum];

        foreach ($example->getFunctionReflection()->getParameters() as $position => $parameter) {
            if (!array_key_exists($position, $data)) {
                continue;
            }

            $collaborators->set($parameter->getName(), $data[$position]);
        }
    }

    public function teardown(
        ExampleNode $example,
        Specification $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators
    ): void {
    }

    public function getPriority(): int
    {
        return 50;
    }

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

        $reflection = $specification->getClassReflection();

        if (!$reflection->hasMethod($dataProviderMethod)) {
            return null;
        }

        try {
            $subject = $reflection->newInstance();
            $providedData = $reflection->getMethod($dataProviderMethod)
                ->invoke($subject);
        } catch (Exception $exception) {
            return null;
        }

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
