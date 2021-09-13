<?php
namespace R64\ContentImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\MapIterator;
use Monolog\Handler\IFTTTHandler;
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


        if ($this->file->extension() === 'txt' && is_null($this->delimeter)) {
            throw new \Exception("delimeter option is required when dealing with txt files");
        }

        $delimeter = $this->file->extension() === 'txt' ? $this->delimeter : ',';

        $output = (new FileProcessor())->read($this->file->url, $delimeter);

        if (get_class($output) === \Generator::class) {

            $this->processGeneratorOutput($output);
        } else {
            $this->processCollectionOutput($output);
        }

        $this->file->markAsProcessed();

        Storage::disk('local')->delete($this->file->url);
    }

    private function processGeneratorOutput(\Generator $output)
    {
        foreach ($output as $item) {
            $this->processCollectionOutput($item);
        }
    }

    private function processCollectionOutput(Collection $collection)
    {
        $records = array_map(fn ($record) => array_change_key_case($record, CASE_LOWER), $collection->toArray());
        ImportedContent::create([
            'file_id' => $this->file->id,
            'data' => mb_convert_encoding(array_values($records), 'UTF-8', 'UTF-8')
        ]);
    }
}
