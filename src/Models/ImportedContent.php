<?php
namespace R64\ContentImport\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedContent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'json'
    ];
}
