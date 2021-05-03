<?php
namespace R64\ContentImport\Facades;

use Illuminate\Support\Facades\Facade;
use R64\ContentImport\MapImportedContent;

class ContentImport extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MapImportedContent::class;
    }
}
