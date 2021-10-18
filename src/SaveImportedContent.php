<?php

namespace R64\ContentImport;

use App\Models\DebtAccount;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function Symfony\Component\Translation\t;

class SaveImportedContent implements ImportableModel
{
    protected $model;

    protected $dependencies = [];

    protected $models = [];

    protected $uniqueFields = [];

    protected $beforeUpdate = null;

    protected $canUpdateCallback = null;

    protected $afterUpdateCallback = null;

    protected $shouldSkipOnCreateCallback = null;

    protected $customAttributesToUpdateCallback = null;

    protected $canCreateOrUpdateCallback = null;

    protected $afterCreatedCallback = null;

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


    public function shouldSkipOnCreate(Closure $closure = null)
    {
        $this->shouldSkipOnCreateCallback = $closure;

        return $this;
    }

    public function afterCreatedCallback(Closure $closure = null)
    {
        $this->afterCreatedCallback = $closure;

        return $this;
    }

    public function afterUpdate(Closure $afterUpdate = null)
    {
        $this->afterUpdateCallback = $afterUpdate;

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
        try {

            $relationships->each(function ($items, $relation) {

                $relation = str_replace('@', '', $relation);

                $relationType = (new \ReflectionClass($this->model->{$relation}()))->getShortName();

                $relatedModel = $this->model->{$relation}()->getRelated();

                if ($relationType === 'BelongsToMany') {
                    $modelIds = [];

                    foreach ($items as $item) {
                        if (is_array($item)) {
                            $relationShipModel = get_class($relatedModel);

                            $modelIds[] = $this->saveModel(new $relationShipModel, $item)->getKey();
                        }
                    }

                    $this->model->{$relation}()->sync(array_filter($modelIds));

                    return;
                }
                if ($relationType === 'MorphMany') {

                    $morphType = $this->model->{$relation}()->getMorphType();
                    $morphId = $this->guessMorphIdFromType($morphType);

                    foreach ($items as $item) {

                        $keys = Arr::except(array_merge($item, [
                            $morphType => get_class($this->model),
                            $morphId => $this->model->id
                        ]),'value');

                        $existingModel = $relatedModel->where($keys)->first();

                        if ($existingModel) {

                            $this->optimisticUpdate($existingModel, $item);

                        } else {

                            $this->model->{$relation}()->create($item);
                        }
                    }

                    return;
                }

                $foreignKey = $this->model->{$relation}()->getForeignKeyName();

                $items[$foreignKey] = $this->model->getKey();

                $this->saveModel($relatedModel, $items);
            });
        } catch (\Exception $exception) {}
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

                if (!$this->canBeSaved($model)) {
                    return;
                }

                $this->optimisticUpdate($model, $items);

            });
        }

        return tap($model, function ($model) use ($items) {
            $model->forceFill(array_merge($items));

            if (!$this->canBeSaved($model)) {
                return;
            }

            if ($this->shouldSkipOnCreateCallback) {
                call_user_func($this->shouldSkipOnCreateCallback, $model);
            }

            $model->savingFromImport();

        });
    }

    protected function canBeSaved(Model $model)
    {
        $canCreateOrUpdateCallback = $this->canCreateOrUpdateCallback;

        if (!$canCreateOrUpdateCallback) {
            return true;
        }

        if ($canCreateOrUpdateCallback($model)) {
            return true;
        }

        return false;
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
        $items = $this->getCustomAttributesToUpdate($existedModel, $items);

        if (!$this->beforeUpdate) {
            return $items;
        }

        $callback = $this->beforeUpdate;

        return collect($items)->filter(function ($value, $attribute) use ($existedModel, $callback) {
            return !$callback($existedModel, $attribute);
        })->toArray();
    }

    protected function getCustomAttributesToUpdate(Model $model, array $items): array
    {
        if (!$this->customAttributesToUpdateCallback) {
            return $items;
        }

        $customAttributesToUpdate = call_user_func($this->customAttributesToUpdateCallback, $model);

        if (!$customAttributesToUpdate) {
            return [];
        }

        return collect($items)->filter(function ($value, $attribute) use ($customAttributesToUpdate) {
            return in_array($attribute, $customAttributesToUpdate);
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
                        $query->where($attribute, $items[$attribute]);
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
        $model->update($items);

        if ($this->afterUpdateCallback) {

            call_user_func($this->afterUpdateCallback, $model->refresh());
        }

        return $model->refresh();
    }

    private function guessMorphIdFromType(string $type)
    {
        $suffix = Arr::last(explode('_', $type));

        return str_replace("_$suffix", "_id", $type);
    }
}
