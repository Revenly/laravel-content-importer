<?php
namespace R64\ContentImport\Facades;
use Illuminate\Support\Facades\Facade;

class ContentImport  extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'content-import';
    }
}
