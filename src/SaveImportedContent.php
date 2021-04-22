<?php
namespace R64\ContentImport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SaveImportedContent implements ImportableModel
{
    protected $model;

    protected $dependencies = [];

    protected $models = [];

    protected $uniqueFields = [];

    protected $beforeUpdate = null;

    protected $canUpdateCallback = null;

    public function withModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function withBeforeUpdate(Closure $beforeUpdate = null)
    {
        $this->beforeUpdate = $beforeUpdate;

        return $this;
    }

    public function canUpdate(Closure $canUpdateCallback = null)
    {
        $this->canUpdateCallback = $canUpdateCallback;

        return $this;
    }

    public function run(array $items, array $uniqueFields = [], array $models = [], array $dependencies = []): Model
    {
        $this->dependencies = $dependencies;

        $this->uniqueFields = $uniqueFields;

        $this->models = $models;

        $modelItems = collect($items)->filter(function($value, $key) {
            return !$this->isRelationAttribute($key);
        })->toArray();

        $relationships = collect($items)->filter(function($value, $key) {
            return $this->isRelationAttribute($key);
        });

        $modelItems = $this->handleDependencies($modelItems);

        $model = $this->saveModel($modelItems);

        $this->handleModelRelationships($model, $relationships);

        return $model;
    }

    protected function handleModelRelationships(Model $model, Collection $relationships)
    {
        $relationships->each(function ($items, $relation) use ($model) {
            $relation = str_replace('@', '', $relation);

            $model->{$relation}()->create($items);
        });
    }

    protected function saveModel(array $items): Model
    {
        $existedModel = $this->getModelIfExists(...func_get_args());

        if ($existedModel && !$this->shouldUpdateModel($existedModel)) {
            return $existedModel;
        }

        if ($existedModel) {
            $items = $this->handleItemsBeforeUpdate($existedModel, $items);

            return tap($existedModel, function ($model) use ($items) {
                $model->forceFill($items)->update();
            });
        }

        return tap($this->model, function ($model) use ($items) {
            $model->forceFill($items);

            $model->save();
        });
    }

    protected function shouldUpdateModel(Model $model): bool
    {
        $callback = $this->canUpdateCallback;

        if (!$callback) {
            return true;
        }

        return $callback($model);
    }

    protected function handleItemsBeforeUpdate(Model $existedModel, array $items): array
    {
        if (!$this->beforeUpdate) {
            return $items;
        }

        $callback = $this->beforeUpdate;

        return collect($items)->filter(function ($value, $attribute) use ($existedModel, $callback) {
            return !$callback($existedModel, $attribute);
        })->toArray();
    }

    protected function getModelIfExists(array $items): ?Model
    {
        $uniqueFields = array_key_exists(get_class($this->model), $this->uniqueFields) ? $this->uniqueFields[get_class($this->model)] : [];

        $query = $this->model::query()->where(function ($query) use ($items, $uniqueFields) {
            collect($uniqueFields)->each(function ($unique) use ($query, $items) {
                $query->orWhere($unique, $items[$unique]);
            });
        });

        return $query->first();
    }


    protected function handleDependencies(array $items): array
    {
        if (!$this->models) {
            return $items;
        }

        $dependencies = collect(Arr::get($this->dependencies, get_class($this->model), []))->map(function ($depend) {
            return $this->models[$depend]->id;
        });

        if ($dependencies->isNotEmpty()) {
            $items = $dependencies->merge($items)->toArray();
        }

        return $items;
    }

    protected function isRelationAttribute($attribute): bool
    {
        return Str::startsWith($attribute, '@');
    }

}
