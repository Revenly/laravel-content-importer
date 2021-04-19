<?php
namespace R64\ContentImport\Imports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ShouldQueueWithoutChain;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use R64\ContentImport\Importable;
use R64\ContentImport\Models\File;
use R64\ContentImport\Models\ImportedContent;

class BatchContentImport implements ToCollection, WithChunkReading, WithHeadingRow, ShouldQueueWithoutChain, WithEvents
{
    public static $processedFile;

    public $file;

    public function __construct(File $file)
    {
        $this->file = $file;

        static::$processedFile = $file;
    }

    public function chunkSize(): int
    {
        return (int) config('content_import.import_chunck_size') ?? 1000;
    }

    public function headingRow(): int
    {
        return (int) config('content_import.heading_row') ?? 1;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => [self::class, 'afterImport']
        ];
    }

    public static function afterImport()
    {
        static::$processedFile->markAsProcessed();
    }

    public function collection(Collection $collection)
    {
        Importable::create([
            'file_id' => $this->file->id,
            'data' => $collection->toArray()
        ]);
    }
}
