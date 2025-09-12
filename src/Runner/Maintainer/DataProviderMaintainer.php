<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Runner\Maintainer;

use DBorsatto\PhpSpec\DataProvider\AnnotationExtractor;
use Override;
use PhpSpec\Loader\Node\ExampleNode;
use PhpSpec\Runner\CollaboratorManager;
use PhpSpec\Runner\Maintainer\Maintainer;
use PhpSpec\Runner\MatcherManager;
use PhpSpec\Specification;

use function array_key_exists;
use function preg_match;

final class DataProviderMaintainer implements Maintainer
{
    private const string EXAMPLE_NUMBER_PATTERN = '/^(\d+)\)/';

    #[Override]
    public function supports(ExampleNode $example): bool
    {
        $annotationExtractor = new AnnotationExtractor();
        $providedData = $annotationExtractor->extract($example);

        return $providedData !== null;
    }

    #[Override]
    public function prepare(
        ExampleNode $example,
        Specification $context,
        MatcherManager $matchers,
        CollaboratorManager $collaborators,
    ): void {
        $exampleNumber = $this->getExampleNumber($example);
        $annotationExtractor = new AnnotationExtractor();
        $providedData = $annotationExtractor->extract($example);
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

    private function getExampleNumber(ExampleNode $node): int
    {
        $title = $node->getTitle();
        if (preg_match(self::EXAMPLE_NUMBER_PATTERN, $title, $matches) === 0) {
            return 0;
        }

        return (int) $matches[1] - 1;
    }
}
