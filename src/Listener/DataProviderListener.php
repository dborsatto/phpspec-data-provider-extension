<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Listener;

use DBorsatto\PhpSpec\DataProvider\Annotation\Parser;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Loader\Node\ExampleNode;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function is_array;

class DataProviderListener implements EventSubscriberInterface
{
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
        foreach ($specification->getExamples() as $example) {
            $functionReflection = $example->getFunctionReflection();
            if (!$functionReflection instanceof ReflectionMethod) {
                continue;
            }

            $dataProviderMethod = Parser::getDataProvider($functionReflection);
            if ($dataProviderMethod === null) {
                continue;
            }

            $classReflection = $specification->getClassReflection();
            if (!$classReflection->hasMethod($dataProviderMethod)) {
                return;
            }

            $subject = $classReflection->newInstance();
            $providedData = $classReflection->getMethod($dataProviderMethod)
                ->invoke($subject);

            if (!is_array($providedData)) {
                continue;
            }

            /** @var list<list<mixed>> $providedData */
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
