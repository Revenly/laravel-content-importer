<?php

namespace R64\ContentImport\Tests\Unit;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Processors\CsvProcessor;
use R64\ContentImport\Tests\TestCase;

class CsvProcessorTest extends TestCase
{
    /** @test */
    public function it_can_process_csv_files()
    {
        Storage::fake('local');
        Storage::disk('local')->putFileAs('imports/1/', new File('tests/files/test_import.csv'), 'test_import.csv');

        $rows = ( new CsvProcessor())->read('imports/1/test_import.csv');

        $this->assertInstanceOf("League\Csv\MapIterator", $rows);
    }
}
