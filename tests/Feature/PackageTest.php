<?php

declare(strict_types=1);

namespace SwadhinSikder\LaravelRowIn\Tests\Feature;

use SwadhinSikder\LaravelRowIn\RowInServiceProvider;
use SwadhinSikder\LaravelRowIn\Tests\TestCase;

class PackageTest extends TestCase
{
    public function test_package_service_provider_is_loaded(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(
                RowInServiceProvider::class,
            ),
        );
    }
}
