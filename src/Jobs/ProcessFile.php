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

    public File $file;

    public ?string $delimeter;

    public function __construct(File $file, string $delimeter = 'null')
    {
        $this->file = $file;

        $this->delimeter = $delimeter;


    }

    public function handle()
    {
        if (!Storage::disk('local')->exists($this->file->url)) {
            Storage::disk('local')->put(
                $this->file->url,
                Storage::disk($this->file->disk)->get($this->file->url)
            );
        }

        $fileExtension = strtolower($this->file->extension());

        if ($fileExtension === 'txt' && is_null($this->delimeter)) {
            throw new \Exception("delimeter option is required when dealing with txt files");
        }

        $delimeter = $fileExtension === 'txt' ? $this->delimeter : ',';

        try {
            $output = (new FileProcessor())->read($this->file->url, $delimeter);

            collect($output)
                ->chunk(100)
                ->each(fn($chunk) => $this->processCollectionOutput($chunk));

            $this->file->markAsProcessed();

            Storage::disk('local')->delete($this->file->url);
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
