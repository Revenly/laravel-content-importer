<?php
namespace R64\ContentImport\Commands;

use Illuminate\Console\Command;
use R64\ContentImport\Jobs\ProcessFile;
use R64\ContentImport\Models\File;

class ProcessImportedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:process {--D|delimeter=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process files imported';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $delimeter = $this->option('delimeter');

        File::unprocessed()
            ->onlyExtensions(config('content_import.extensions'))
            ->each(fn($file) => ProcessFile::dispatch($file, $delimeter));
    }
}
