<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider\Listener;

use DBorsatto\PhpSpec\DataProvider\Annotation\Parser;
use PhpSpec\Event\SpecificationEvent;
use PhpSpec\Loader\Node\ExampleNode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function is_array;

class DataProviderListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'beforeSpecification' => ['beforeSpecification'],
        ];
    }

    public function beforeSpecification(SpecificationEvent $event): void
    {
        $examplesToAdd = [];
        foreach ($event->getSpecification()->getExamples() as $example) {
            $dataProviderMethod = Parser::getDataProvider($example->getFunctionReflection());
            if ($dataProviderMethod === null) {
                continue;
            }

            $specification = $example->getSpecification();
            if ($specification === null) {
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

            foreach ($providedData as $index => $dataRow) {
                $examplesToAdd[] = new ExampleNode(
                    $index + 1 . ') ' . $example->getTitle(),
                    $example->getFunctionReflection()
                );
            }
        }

        foreach ($examplesToAdd as $example) {
            $event->getSpecification()->addExample($example);
        }
    }
}
