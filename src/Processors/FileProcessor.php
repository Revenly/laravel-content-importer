<?php

namespace R64\ContentImport\Processors;

use Illuminate\Support\Facades\Storage;
use SplFileObject;

class FileProcessor implements FileProcessorContract
{

    public function read(string $path, ?string $delimeter)
    {

        $file = new SplFileObject(Storage::disk('local')->path($path), 'r');

        $count = $this->getFileCount($file);

        $headers = null;
        $content = collect([]);

        while (!$file->eof()) {
            if ($file->key() === 0) {

                $headers = array_map(function ($header) {
                    return str_replace(' ', '', $header);
                },  $this->getRow($file->current(), $delimeter));

                $file->next();
            }

            $row = $this->getRow($file->current(), $delimeter);

            if (count($row) === count($headers)) {

                $content->push(array_combine($headers, $row));

                if ($file->key() % 100 === 0 ) {

                     yield $content;
                     $content = collect([]);
                }
            }
            $file->next();
        }

        if ($count <= 100) {
            yield $content;
        }
    }

    private function getRow(string $row, string $delimiter )
    {
        return explode($delimiter, str_replace('"', '', trim($row)));
    }

    private function getFileCount(SplFileObject $file)
    {
        $file->seek($file->getSize());
        $count = $file->key();
        $file->seek(0);

        return $count;
    }
}
