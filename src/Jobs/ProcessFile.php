<?php
namespace R64\ContentImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use R64\ContentImport\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use R64\ContentImport\Models\ImportedContent;
use R64\ContentImport\Processors\CsvProcessor;
use R64\ContentImport\Processors\TxtProcessor;

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
            throw new \Exception("txt-delimeter option is requred when dealing with txt files");
        }
        logger()->debug(config('content_import'));

        $processingClass = config(sprintf('content_import.%s', $this->file->extension()));
        logger()->debug("file: " . $this->file->url);
        logger()->debug("delimiter: " . $this->delimeter);
        logger()->debug("processing class: " . $processingClass);
        $processor = app()->make($processingClass);

        collect($processor->read($this->file->url, $this->delimeter))
            ->chunk(config('content_import.chunk_size', 1000))
            ->each(function ($chunk) {
                $records = array_map(fn ($record) => array_change_key_case($record, CASE_LOWER), $chunk->toArray());
                ImportedContent::create([
                    'file_id' => $this->file->id,
                    'data' => array_values($records)
                ]);
            });

        $this->file->markAsProcessed();
    }
}
