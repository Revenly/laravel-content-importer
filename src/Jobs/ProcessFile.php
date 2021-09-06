<?php
namespace R64\ContentImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\MapIterator;
use R64\ContentImport\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use R64\ContentImport\Models\ImportedContent;

class ProcessFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public File $file;

    public string $delimeter;

    public function __construct(File $file, string $delimeter=null)
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

        if ($this->file->extension() === 'txt' && is_null($this->delimeter)) {
            throw new \Exception("delimeter option is required when dealing with txt files");
        }

        $processingClass = config(sprintf('content_import.%s', $this->file->extension()));

        $processor = app()->make($processingClass);


        $output = $processor->read($this->file->url, $this->delimeter);


        if (get_class($output) === \Generator::class) {
            $this->processGeneratorOutput($output);
        } else {
            $this->processCollectionOutput($output);
        }

        $this->file->markAsProcessed();
    }

    private function processGeneratorOutput(\Generator $output)
    {
        foreach ($output as $item) {
            $this->processCollectionOutput($item);
        }
    }

    private function processCollectionOutput($collection)
    {
            collect($collection)
                ->chunk(1)
                ->each(function ($chunk) {
                    $records = array_map(fn ($record) => array_change_key_case($record, CASE_LOWER), $chunk->toArray());
                    ImportedContent::create([
                        'file_id' => $this->file->id,
                        'data' => mb_convert_encoding(array_values($records), 'UTF-8', 'UTF-8')
                    ]);
                });
    }
}
