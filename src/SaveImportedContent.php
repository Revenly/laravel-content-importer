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

        $modelItems = collect($items)->filter(function ($value, $key) {
            return !$this->isRelationAttribute($key);
        })->toArray();

        $relationships = collect($items)->filter(function ($value, $key) {
            return $this->isRelationAttribute($key);
        });

        $modelItems = $this->handleDependencies($modelItems);

        $this->model = $this->saveModel($this->model, $modelItems);

        $this->handleModelRelationships($relationships);

        return $this->model;
    }

    protected function handleModelRelationships(Collection $relationships)
    {
        $relationships->each(function ($items, $relation) {
            $relation = str_replace('@', '', $relation);

            $foreignKey = $this->model->{$relation}()->getQualifiedForeignKeyName();

            $relatedModel = $this->model->{$relation}()->getRelated();

            $items[$foreignKey] = $this->model->getKey();

            $this->saveModel($relatedModel, $items);
        });
    }

    protected function saveModel(Model $model, array $items): Model
    {
        $existedModel = $this->getModelIfExists($model, $items);

        if ($existedModel && !$this->shouldUpdateModel($existedModel)) {
            return $existedModel;
        }

        if ($existedModel) {
            $items = $this->handleItemsBeforeUpdate($existedModel, $items);

            return tap($existedModel, function ($model) use ($items) {
                $model->forceFill($items);

                $model->savingFromImport();
            });
        }

        return tap($model, function ($model) use ($items) {
            $model->forceFill(array_merge($items));

            $model->savingFromImport();
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

    protected function getModelIfExists(Model $model, array $items): ?Model
    {
        $uniqueFields = array_key_exists(get_class($model), $this->uniqueFields) ? $this->uniqueFields[get_class($model)] : [];

        if (!$uniqueFields) {
            return null;
        }

        $query = $model::query()->where(function ($query) use ($items, $uniqueFields) {

            $and = Arr::get($uniqueFields, 'and', []);

            $or = Arr::get($uniqueFields, 'or', []);

            if ($and) {
                collect($and)->each(function ($attribute) use ($query, $items) {
                    $query->where(function ($query) use ($attribute, $items) {
                        $query->whereNotNull($attribute)->where($attribute, $items[$attribute]);
                    });
                });
            }

            if ($or) {
                $query->orWhere(function ($query) use ($or, $items) {
                    collect($or)->each(function ($attribute) use ($query, $items) {
                        $query->orWhere(function ($query) use ($attribute, $items) {
                            $query->whereNotNull($attribute)->where($attribute, $items[$attribute]);
                        });
                    });
                });
            }
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

    /**
     * @param       $model
     * @param array $items
     *
     * @return mixed
     */
    protected function optimisticUpdate(Model $model, array $items): Model
    {
        $updated = false;

        do {
            $model = $model->fresh();
            $updated = $model::query()->whereId($model->id)
                ->where('updated_at', '=', $model->updated_at)
                ->update($items);
        } while (!$updated);

        return $model;
    }
}
