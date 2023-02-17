<?php
namespace R64\ContentImport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Jobs\ProcessFile;
use R64\ContentImport\Models\File;

class ImportFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:import {--F|folder=} {--delete=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and process files';

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

        $folder = $this->option('folder');
        $deleteFile = (bool) $this->option('delete') ?? true;

        $this->createFilesFromDisk($fileSystem, $folder);
        $this->processFiles($deleteFile);
    }

    private function saveImportedFile(string $url)
    {
        $model = config('content_import.model') ?? File::class;

        $file = $model::where('url', '=', $url)->where('disk', '=', $this->disk)->first();

        if (is_null($file)) {
            $model::create(['url' => $url, 'disk' => $this->disk]);
        }
    }

    protected function createFilesFromDisk(\Illuminate\Contracts\Filesystem\Filesystem $fileSystem, $folder): void
    {
        collect($fileSystem->directories($folder ?? config('content_import.directory')))
            ->lazy()
            ->each(fn($path) => collect($fileSystem->allFiles($path))
                ->reject(function ($file) {
                    $extension = strtolower((string) Arr::last(explode('.', $file)));

                    $availableExtensions = str_replace('.', '', (string) config('content_import.extensions'));
                    $availableExtensions = array_map(fn($extension) => strtolower($extension), $availableExtensions);

                    return !in_array($extension, $availableExtensions);
                })->each(fn($file) => $this->saveImportedFile($file)));
    }

    protected function processFiles(bool $deleteFile): void
    {
        File::unprocessed()
            ->onlyExtensions(array_map('strtolower', config('content_import.extensions')))
            ->each(fn($file) => ProcessFile::dispatch($file, $deleteFile));
    }
}
