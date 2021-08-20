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


    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function handle()
    {
        if (!Storage::disk('local')->exists($this->file->url)) {
            Storage::disk('local')->put(
                $this->file->url,
                Storage::disk($this->file->disk)->get($this->file->url)
            );
        }


        $processor = null;

        if ($this->file->extension() === 'csv' || $this->file->extension() === 'xlsx') {
            $processor = new CsvProcessor();
        }

        if ($this->file->extension() === 'txt') {
            $processor = new TxtProcessor();
        }

        collect($processor->read($this->file->url))
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
