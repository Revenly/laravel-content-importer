<?php
namespace Tests\Commands;

use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;
use R64\ContentImport\Models\File;
use R64\ContentImport\Tests\TestCase;

class ProcessFileCommandTest extends TestCase
{
    /** @test */
    public function it_should_process_unprocessed_files()
    {
        $this->markTestSkipped();
        Excel::fake();
        $file = factory(File::class)->create(['processed_at' => null]);
        Artisan::call('files:process');
        Excel::assertImported($file->url);
    }
}
