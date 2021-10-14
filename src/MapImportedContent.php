<?php

namespace R64\ContentImport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    protected $skipOnCreateCallback = null;

    protected $customAttributesToUpdateCallback = null;

    protected $canCreateOrUpdateCallback = null;

    protected $afterUpdateCallback = null;

    protected $afterCreatedCallback = null;

    protected $shouldSkipRow = null;

    protected $mappedRows = [];

    protected $validators;

    protected $dirtyRows = [];

    protected $additionalRows = [];

    protected $saveRelationshipCallback = null;

    public function __construct(ImportableModel $importableModel = null)
    {
        $this->setImportableModelClass($importableModel);
    }

    public function init(array $content = [], ImportableModel $importableModel = null): self
    {
        $this->content = collect($content);

        $this->setImportableModelClass($importableModel);

        return $this;
    }

    public function shouldSkipOnCreate(Closure $skipOnCreateCallback): self
    {
        $this->skipOnCreateCallback = $skipOnCreateCallback;

        return $this;
    }

    public function withMappedRow(array $rowsToMap): self
    {
        $this->rowsToMap = collect($rowsToMap);

        return $this;
    }

    public function withAdditionalRows(array $additionalRows): self
    {
        $this->additionalRows = $additionalRows;

        return $this;
    }

    public function shouldSkipRow(Closure $shouldSkipRow): self
    {
        $this->shouldSkipRow = $shouldSkipRow;

        return $this;
    }

    public function withCasting(array $casts): self
    {
        $this->casts = collect($casts);

        return $this;
    }

    public function afterCreated(Closure $afterCreatedCallback = null): self
    {
        $this->afterCreatedCallback = $afterCreatedCallback;

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

    public function customAttributesToUpdate(Closure $customAttributesToUpdateCallback = null)
    {
        $this->customAttributesToUpdateCallback = $customAttributesToUpdateCallback;

        return $this;
    }

    public function canCreateOrUpdate(Closure $canCreateOrUpdateCallback = null)
    {
        $this->canCreateOrUpdateCallback = $canCreateOrUpdateCallback;

        return $this;
    }

    public function withBeforeUpdate(Closure $beforeUpdate = null)
    {
        $this->beforeUpdate = $beforeUpdate;

        return $this;
    }

    public function afterUpdate(Closure $afterUpdate): self
    {
        $this->afterUpdateCallback = $afterUpdate;

        return $this;
    }

    public function map(): self
    {
        $this->mappedRows = $this->content->map(function ($row) {
            $row = array_merge($row, $this->additionalRows);
            return [
                'row' => $row,
                'data' => $this->mapRow($row),
            ];
        })->reject(function ($data) {
            return $this->shouldSkipRow && call_user_func($this->shouldSkipRow, $data['row']);
        })->toArray();

        return $this;
    }

    public function saveRelationshipCallback(Closure $closure = null)
    {
        $this->saveRelationshipCallback = $closure;

        return $this;
    }

    public function store(array $mappedRows = []): self
    {
        $storeRows = $this->mappedRows;

        if ($mappedRows) {
            $storeRows = $mappedRows;
        }

        collect($storeRows)->pluck('data')->map(function ($rowData) {

            $this->models = [];

            $this->dependencies = [];

            collect($rowData)->each(function (array $items, string $model) {
                $this->setDependencies($model, Arr::get($items, 'depends_on', []));

                $items = $this->removeUnwantedElementFromItems($items);

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
            ->canCreateOrUpdate($this->canCreateOrUpdateCallback)
            ->customAttributesToUpdate($this->customAttributesToUpdateCallback)
            ->withBeforeUpdate($this->beforeUpdate)
            ->shouldSkipOnCreate($this->skipOnCreateCallback)
            ->afterCreatedCallback($this->afterCreatedCallback)
            ->afterUpdate($this->afterUpdateCallback)
            ->run($items, $this->uniqueFields, $this->models, $this->dependencies);
    }

    protected function mapModelAttributes(array $rowToMap, array $row, string $model): array
    {
        $rowToMap = collect($rowToMap);

         return collect($rowToMap)->map(function ($column, $attribute) use ($row, $model, $rowToMap) {
            if ($this->isRelationAttribute($attribute)) {

                if (is_array($column) && !is_string(Arr::first($column))) {
                    return collect($column)->map(fn($cols) => $this->mapModelAttributes($cols, $row, $model));
                }

                return $this->mapModelAttributes($column, $row, $model);
            }

            if (is_array($column)) {
                return $column;
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
                'failed_reason' => $e->getMessage(),
            ];
        }
    }

    protected function removeUnwantedElementFromItems(array $items): array
    {
        $notNeeded = [
            'depends_on',
        ];

        return collect($items)->forget($notNeeded)->toArray();
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
