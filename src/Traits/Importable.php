<?php
namespace R64\ContentImport\Traits;

trait Importable
{
    public function savingFromImport()
    {
        $this->imported_at = now();

        $this->save();
    }
}
