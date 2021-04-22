<?php
namespace R64\ContentImport\Tests;

use File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use R64\ContentImport\ContentImportServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            ContentImportServiceProvider::class,
        ];
    }

    protected function setUpDatabase(Application $app)
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
