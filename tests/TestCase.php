<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SwadhinSikder\LaravelRowIn\RowInServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            RowInServiceProvider::class,
        ];
    }
}
