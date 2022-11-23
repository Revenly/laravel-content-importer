<?php
namespace R64\ContentImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use R64\ContentImport\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use R64\ContentImport\Models\ImportedContent;
use R64\ContentImport\Processors\FileProcessor;

class ProcessFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private File $file;
    private bool $deleteFile;

    /**
     * @param File $file
     * @param bool $deleteFile
     */
    public function __construct(File $file, bool $deleteFile)
    {
        $this->file       = $file;
        $this->deleteFile = $deleteFile;
    }

    public function handle()
    {
        if (!Storage::disk('local')->exists($this->file->url)) {
            Storage::disk('local')->put(
                $this->file->url,
                Storage::disk($this->file->disk)->get($this->file->url)
            );
        }

        try {
            $output = (new FileProcessor())->read($this->file->url);

            collect($output)
                ->chunk(100)
                ->each(fn($chunk) => $this->processCollectionOutput($chunk));

            $this->file->markAsProcessed();

            if ($this->deleteFile) {
                Storage::disk('local')->delete($this->file->url);
            }
        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
    }

    private function processCollectionOutput(Collection $collection)
    {
        ImportedContent::create([
            'file_id' => $this->file->id,
            'data' => mb_convert_encoding(array_values($collection->toArray()), 'UTF-8', 'UTF-8')
        ]);
    }
}
