<?php

namespace R64\ContentImport;

use R64\ContentImport\Models\ImportedContent;

class ContentImport
{
    protected $importable;


    public function __construct(Importable $importable)
    {
        $this->importable = $importable;
    }

    public function save(array $data)
    {
        $model =  $this->importable->resolveImportableModel() ?? $this->importedContent;
        return $model->create($data);
    }
}
