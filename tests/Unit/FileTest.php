<?php

namespace R64\ContentImport\Tests\Unit;

use R64\ContentImport\Models\File;
use R64\ContentImport\Tests\TestCase;

class FileTest extends TestCase
{
    /**
    * @test
    */
    public function it_can_filter_by_extensions()
    {
        File::create([
            'url' => 'some_file.csv',
            'disk' => 's3'
        ]);

        File::create([
            'url' => 'some_file.zip',
            'disk' => 's3'
        ]);

        File::create([
            'url' => 'some_file.xls',
            'disk' => 's3'
        ]);

        $this->assertCount(1, File::onlyExtensions(['.csv'])->get());
        $this->assertCount(1, File::onlyExtensions(['.xls'])->get());
        $this->assertCount(2, File::onlyExtensions(['.csv', '.xls'])->get());
        $this->assertCount(3, File::onlyExtensions(['.csv', '.xls', '.zip'])->get());
        $this->assertCount(3, File::onlyExtensions([])->get());
    }
}
