<?php
namespace Tests\Jobs;

use Maatwebsite\Excel\Facades\Excel;
use R64\ContentImport\Jobs\ProcessFile;
use R64\ContentImport\Models\File;
use R64\ContentImport\Tests\TestCase;

class ProcessFileTest extends TestCase
{
    /**
     * @test
     */
    public function should_process_imported_file()
    {
        Excel::fake();

        $file = factory(File::class)->create();

        ProcessFile::dispatchNow($file);

        Excel::assertImported($file->url);
    }
}
