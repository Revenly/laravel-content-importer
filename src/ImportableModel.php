<?php
namespace R64\ContentImport;

use Illuminate\Database\Eloquent\Model;

interface ImportableModel
{
    public function withModel(Model $model): self;

    public function run(array $items, array $uniqueFields = [], array $models = [], array $dependencies = []): Model;
}
