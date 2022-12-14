<?php

namespace Vmorozov\LaravelFluentdLogger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vmorozov\LaravelFluentdLogger\LaravelFluentdLoggerServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelFluentdLoggerServiceProvider::class,
        ];
    }
}
