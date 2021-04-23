<?php

namespace R64\ContentImport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MapImportedContent
{
    protected $content;

    protected $rowsToMap;

    protected $uniqueFields = [];

    protected $casts = [];

    protected $dependencies = [];

    protected $models = [];

    protected $importableModel;

    protected $beforeUpdate = null;

    protected $canUpdateCallback = null;

    protected $mappedAttributes = [];

    public function __construct(array $content, ImportableModel $importableModel = null)
    {
        $this->content = collect($content);

        $this->setImportableModelClass($importableModel);
    }

    public function withMappedRow(array $rowsToMap): self
    {
        $this->rowsToMap = collect($rowsToMap);

        return $this;
    }

    public function withCasting(array $casts): self
    {
        $this->casts = collect($casts);

        return $this;
    }

    public function withUniqueFields(array $uniqueFields): self
    {
        $this->uniqueFields = $uniqueFields;

        return $this;
    }

    public function canUpdate(Closure $canUpdateCallback = null)
    {
        $this->canUpdateCallback = $canUpdateCallback;

        return $this;
    }

    public function withBeforeUpdate(Closure $beforeUpdate = null)
    {
        $this->beforeUpdate = $beforeUpdate;

        return $this;
    }

    public function map(): void
    {
        $this->content->each(function ($row) {
            $this->mapModels($row);
        });
    }

    public function saveModels()
    {
        $this->mappedAttributes->map(function ($items, string $model) {
            $model = $this->savingModel(new $model, $items);

            $this->setModel($model);
        });
    }

    protected function mapModels(array $row): void
    {
        $this->mappedAttributes = $this->rowsToMap->map(function ($rowToMap, $model) use ($row) {
            return $this->mapModelAttributes($rowToMap, $row, $model);
        });
        dd($this->mappedAttributes);
    }

    protected function savingModel(Model $model, array $items): Model
    {
        return $this->importableModel
        ->withModel(new $model)
        ->canUpdate($this->canUpdateCallback)
        ->withBeforeUpdate($this->beforeUpdate)
        ->run($items, $this->uniqueFields, $this->models, $this->dependencies);
    }

    protected function mapModelAttributes(array $rowToMap, array $row, string $model): array
    {
        $this->setDependencies($model, Arr::get($rowToMap, 'depends_on', []));

        $rowToMap = collect($rowToMap)->forget('depends_on');

        return collect($rowToMap)->map(function ($column, $attribute) use ($row, $model) {
            if ($this->isRelationAttribute($attribute)) {
                return $this->mapModelAttributes($column, $row, $model);
            }

            return $this->retrieveColumnFromRow($column, $attribute, $model, $row);
        })->toArray();
    }

    protected function retrieveColumnFromRow(string $column, string $attribute, string $model, array $row): ?string
    {
        return $this->castAttribute(...func_get_args());
    }

    protected function castAttribute(string $column, string $attribute, string $model, array $row): ?string
    {
        $value = array_key_exists($column, $row) ? $row[$column] : null;

        if (!$this->casts) {
            return $value;
        }

        $castings = $this->casts->filter(function ($value, $key)  use ($model) {
            return $key === $model;
        });

        if (!$castings) {
            return $value;
        }

        $modelCastings = Arr::get($castings, $model, []);

        if (array_key_exists($attribute, $modelCastings)) {
            $callback = $modelCastings[$attribute];

            if (is_callable($callback)) {
                return $callback($row);
            }

            return app()->make($callback)($value);
        }

        return $value;
    }

    protected function isRelationAttribute($attribute): bool
    {
        return Str::startsWith($attribute, '@');
    }

    protected function setModel(Model $model): void
    {
        $this->models[get_class($model)] = $model;
    }

    protected function setDependencies(string $model, array $dependencies): void
    {
        $this->dependencies[$model] = $dependencies;
    }

    protected function setImportableModelClass(ImportableModel $importableModel = null): void
    {
        if (!$importableModel) {
            $this->importableModel = new SaveImportedContent;

            return;
        }

        $this->importableModel = $importableModel;
    }
}
