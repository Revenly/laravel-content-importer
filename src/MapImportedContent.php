<?php

namespace R64\ContentImport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use R64\ContentImport\Events\ValidationFailed;
use R64\ContentImport\Exceptions\ValidationFailedException;
use R64\ContentImport\Pipelines\Pipeline;
use R64\ContentImport\Pipelines\PipelineContract;

class MapImportedContent
{
    protected $content;

    protected $dirtyKeys = [];

    protected $rowsToMap;

    protected $uniqueFields = [];

    protected $casts = [];

    protected $dependencies = [];

    protected $models = [];

    protected $importableModel;

    protected $beforeUpdate = null;

    protected $canUpdateCallback = null;

    protected $mappedRows = [];

    protected $validators;

    protected $dirtyRows = [];

    public function __construct(array $content = [], ImportableModel $importableModel = null)
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

    public function withValidations(array $validators): self
    {
        $this->validators = collect($validators);

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

    public function map(): self
    {
        $this->mappedRows = $this->content->map(function ($row) {
            return [
                'row' => $row,
                'data' => $this->mapRow($row)
            ];
        })->toArray();

        return $this;
    }

    public function store(array $mappedRows = []): self
    {
        $storeRows = $this->mappedRows;

        if ($mappedRows) {
            $storeRows = $mappedRows;
        }

        collect($storeRows)->pluck('data')->map(function ($rowData) {
            collect($rowData)->each(function (array $items, string $model) {
                $model = $this->savingModel(new $model, $items);

                $this->setModel($model);
            });
        });

        return $this;
    }

    protected function mapRow(array $row): array
    {
        return $this->rowsToMap->map(function ($rowToMap, $model) use ($row) {
            return $this->mapModelAttributes($rowToMap, $row, $model);
        })->toArray();
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

        return collect($rowToMap)->map(function ($column, $attribute) use ($row, $model, $rowToMap) {
            if ($this->isRelationAttribute($attribute)) {
                return $this->mapModelAttributes($column, $row, $model);
            }

            return $this->retrieveColumnFromRow($column, $attribute, $model, $row, $rowToMap);
        })->toArray();
    }

    protected function retrieveColumnFromRow(string $column, string $attribute, string $model, array $row, Collection $toMap)
    {
        try {
            $this->validateAttribute(...func_get_args());

            return $this->castAttribute(...func_get_args());
        } catch (ValidationFailedException $e) {
            $this->dirtyRows[] = [
                'row' => $row,
                'data' => $toMap,
                'failed_reason' => $e->getMessage()
            ];
        }
    }

    protected function castAttribute(string $column, string $attribute, string $model, array $row): ?string
    {
        $value = array_key_exists($column, $row) ? $row[$column] : null;

        if (!$this->casts) {
            return $value;
        }

        $castings = $this->casts->filter(function ($value, $key) use ($model) {
            return $key === $model;
        });

        if (!$castings) {
            return $value;
        }

        $modelCastings = (array) Arr::get($castings, $model, []);

        if (array_key_exists($attribute, $modelCastings)) {
            $callback = $modelCastings[$attribute];

            if (is_callable($callback)) {
                return $callback($row);
            }

            if (is_string($callback) && $this->implementsPipelineContract($callback)) {
                return app()->make($callback)($value);
            }

            if (is_array($callback)) {
                return (new Pipeline)($value, $callback);
            }
        }

        return $value;
    }

    protected function validateAttribute(string $column, string $attribute, string $model, array $row)
    {
        $value = array_key_exists($column, $row) ? $row[$column] : null;

        if (!$this->validators) {
            return true;
        }

        $validation = $this->validators->filter(function ($value, $key) use ($model) {
            return $key === $model;
        });

        if (!$validation) {
            return true;
        }

        $modelValidations = (array) Arr::get($validation, $model, []);

        if (array_key_exists($attribute, $modelValidations)) {
            $callback = $modelValidations[$attribute];

            if (is_callable($callback)) {
                return $callback($row);
            }

            if (is_string($callback) && $this->implementsPipelineContract($callback)) {
                return app()->make($callback)($value);
            }

            if (is_array($callback)) {
                return (new Pipeline)($value, $callback);
            }
        }

        return true;
    }

    protected function implementsPipelineContract(string $callback): bool
    {
        return in_array(PipelineContract::class, class_implements($callback));
    }

    protected function isRelationAttribute($attribute): bool
    {
        return Str::startsWith($attribute, '@');
    }

    protected function setModel(Model $model): void
    {
        $this->models[get_class($model)] = $model;
    }

    public function getMappedRows(): array
    {
        return $this->mappedRows;
    }

    public function getDirtyRows(): array
    {

        return $this->dirtyRows;
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