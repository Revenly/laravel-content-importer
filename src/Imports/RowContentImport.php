<?php
namespace R64\ContentImport\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Events\AfterImport;
use R64\ContentImport\Importable;

class RowContentImport implements ToModel
{
    public function model(array $row)
    {
//        return Importable::create([
//            'file_id' =>
//        ])
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
}
