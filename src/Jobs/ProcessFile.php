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

        $stream = fopen(Storage::disk('local')->path($this->file->url), 'r');
        $csv = Reader::createFromStream($stream);

        if (config('content_import.heading_row', true)) {
            $csv->setHeaderOffset(0);
        }

        collect($csv->getRecords())
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
