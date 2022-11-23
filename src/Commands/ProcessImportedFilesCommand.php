<?php
namespace R64\ContentImport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Jobs\ProcessFile;
use R64\ContentImport\Models\File;

class ProcessImportedFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:process {--delete=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process imported files';

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
        $delete = (bool) $this->option('delete') ?? true;

        File::unprocessed()
            ->onlyExtensions(array_map('strtolower', config('content_import.extensions')))
            ->each(fn($file) => ProcessFile::dispatch($file))
            ->each(function ($file) use ($delete) {
                if ($delete) {
                    Storage::disk('local')->delete($file->url);
                }
            });
    }
}
