<?php

declare(strict_types=1);

namespace DBorsatto\PhpSpec\DataProvider;

use DBorsatto\PhpSpec\DataProvider\Listener\DataProviderListener;
use DBorsatto\PhpSpec\DataProvider\Runner\Maintainer\DataProviderMaintainer;
use PhpSpec\Extension;
use PhpSpec\ServiceContainer;

class DataProviderExtension implements Extension
{
    public function load(ServiceContainer $container, array $params): void
    {
        $container->define('event_dispatcher.listeners.data_provider', function (): DataProviderListener {
            return new DataProviderListener();
        }, ['event_dispatcher.listeners']);

        $container->define('runner.maintainers.data_provider', function (): DataProviderMaintainer {
            return new DataProviderMaintainer();
        }, ['runner.maintainers']);
    }
}
