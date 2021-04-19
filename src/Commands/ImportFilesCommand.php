<?php
namespace R64\ContentImport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Models\File;

class ImportFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import files to be processed';

    protected string $disk;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->disk = config('filesystem.default') ?? 's3';
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fileSystem = Storage::disk($this->disk);

        collect($fileSystem->allDirectories(config('content_import.directory')))
            ->lazy()
            ->each(fn($path) =>
                collect($fileSystem->allFiles($path))->each(fn($file) => $this->saveImportedFile($file))
            );
    }

    /**
     * @param string $url
     */
    private function saveImportedFile(string $url)
    {
        $data = ['url' => $url, 'disk' => $this->disk];
        File::updateOrCreate($data, $data);
    }
}
