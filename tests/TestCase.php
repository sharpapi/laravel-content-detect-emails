<?php

declare(strict_types=1);

namespace SharpAPI\ContentDetectEmails\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use SharpAPI\ContentDetectEmails\ContentDetectEmailsProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ContentDetectEmailsProvider::class];
    }
}
