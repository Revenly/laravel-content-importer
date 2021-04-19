<?php
namespace R64\ContentImport\Models;

use R64\ContentImport\Importable;

class ImportedContent extends Importable
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'json'
    ];
}
