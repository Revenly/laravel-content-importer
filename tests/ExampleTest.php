<?php

namespace R64\ContentImport\Tests;

use Orchestra\Testbench\TestCase;
use R64\ContentImport\ContentImportServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [ContentImportServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
