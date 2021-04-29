<?php
namespace R64\ContentImport\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ValidationFailed
{
    use Dispatchable;

    public $row;

    /**
     * Create a new event instance.
     *
     * @param $row
     */
    public function __construct($row)
    {
        $this->row = $row;
    }
}
