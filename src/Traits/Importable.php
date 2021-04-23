<?php
namespace R64\ContentImport\Traits;
trait Importable
{
    public function savingFromImport()
    {
        $this->beforeSavingFromImport();

        $this->save();
    }

    public function beforeSavingFromImport()
    {
        $this->imported_at = now();
    }
}
