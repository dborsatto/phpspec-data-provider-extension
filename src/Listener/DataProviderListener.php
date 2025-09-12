<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Listener;

use DBorsatto\PhpSpec\DataProvider\AnnotationExtractor;
use Override;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Loader\Node\ExampleNode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DataProviderListener implements EventSubscriberInterface
{
    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            'beforeSpecification' => ['handle'],
        ];
    }

    public function handle(SpecificationEvent $event): void
    {
        $examplesToAdd = [];
        $specification = $event->getSpecification();
        $annotationExtractor = new AnnotationExtractor();

        foreach ($specification->getExamples() as $example) {
            $functionReflection = $example->getFunctionReflection();

            $providedData = $annotationExtractor->extract($example);
            if ($providedData === null) {
                continue;
            }

            foreach ($providedData as $index => $dataRow) {
                $examplesToAdd[] = new ExampleNode(
                    $index + 1 . ') ' . $example->getTitle(),
                    $functionReflection,
                );
            }
        }

        foreach ($examplesToAdd as $example) {
            $specification->addExample($example);
        }
    }
}
