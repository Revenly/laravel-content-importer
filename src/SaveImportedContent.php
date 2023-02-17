<?php

namespace R64\ContentImport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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

    protected $afterUpdateCallback = null;

    protected $shouldSkipOnCreateCallback = null;

    protected $customAttributesToUpdateCallback = null;

    protected $canCreateOrUpdateCallback = null;

    protected $beforeCreatedCallback = null;

    protected $afterCreatedCallback = null;

    protected $beforeUpdatedCallback = null;

    protected $firstRun = false;

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

    /**
     * @param null $beforeCreatedCallback
     */
    public function beforeCreatedCallback($beforeCreatedCallback)
    {
        $this->beforeCreatedCallback = $beforeCreatedCallback;

        return $this;
    }

    public function afterCreatedCallback(Closure $closure = null)
    {
        $this->afterCreatedCallback = $closure;

        return $this;
    }

    public function beforeUpdatedCallback(Closure $closure = null)
    {
        $this->beforeUpdatedCallback = $closure;

        return $this;
    }

    public function isFirstRun(bool $firstRun): SaveImportedContent
    {
        $this->firstRun = $firstRun;

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

        $modelItems = collect($items)->filter(fn($value, $key) => !$this->isRelationAttribute($key))->toArray();

        $relationships = collect($items)->filter(fn($value, $key) => $this->isRelationAttribute($key));

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
                            $relationShipModel = $relatedModel::class;

                            $modelIds[] = $this->saveModel(new $relationShipModel, $item)->getKey();
                        }
                    }

                    $this->model->{$relation}()->sync(array_filter($modelIds));

                    return;
                }
                if ($relationType === 'MorphMany') {

                    $morphType = $this->model->{$relation}()->getMorphType();
                    $morphId = $this->guessMorphIdFromType($morphType);

                    // @TODO This neglects the check and update on first run
                    // and inserts all data in 1 multi insert
                    if ($this->firstRun) {
                        $items = array_map(fn ($item) =>
                            array_merge($item, [
                                $morphType => $this->getMorphType($this->model),
                                $morphId => $this->model->id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]),
                            $items
                        );

                        $relatedModel::insert($items);
                    } else {
                        foreach ($items as $item) {

                            $keys = array_merge($item, [
                                $morphType => $this->getMorphType($this->model),
                                $morphId => $this->model->id
                            ]);

                            $existingModel = $this->getModelIfExists($relatedModel, $keys);

                            if ($existingModel) {

                                $customAttributesToUpdate = $this->getCustomAttributesToUpdate($existingModel, $items);

                                if (is_array($customAttributesToUpdate) && is_array(Arr::first($customAttributesToUpdate))) {
                                    if (in_array($keys['attribute'], collect($customAttributesToUpdate)->pluck('attribute')->all())) {
                                        $this->optimisticUpdate($existingModel, $keys);
                                    }
                                } else {
                                    $this->optimisticUpdate($existingModel, $customAttributesToUpdate);
                                }

                            } else {

                                $model = $this->model->{$relation}()->create($item);

                                if ($this->afterCreatedCallback) {
                                    call_user_func($this->afterCreatedCallback, $model, $items);
                                }
                            }
                        }
                    }

                    return;
                }

                $foreignKey = $this->model->{$relation}()->getForeignKeyName();

                $items[$foreignKey] = $this->model->getKey();

                $this->saveModel($relatedModel, $items);
            });
        } catch (\Exception) {}
    }

    protected function saveModel(Model $model, array $items): Model
    {
        $existedModel = $this->getModelIfExists($model, $items);

        if ($existedModel && !$this->shouldUpdateModel($existedModel, $items)) {
            return $existedModel;
        }

        if ($existedModel) {


            $items = $this->handleItemsBeforeUpdate($existedModel, $items);

            return tap($existedModel, function ($model) use ($items) {
                $model->forceFill($items);

                if (!$this->canBeSaved($model)) {
                    return;
                }

                if ($this->beforeUpdatedCallback) {
                    call_user_func($this->beforeUpdatedCallback, $model);
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

            if ($this->beforeCreatedCallback) {
                call_user_func($this->beforeCreatedCallback, $model, $items);
            }

            $model->savingFromImport();

            if ($this->afterCreatedCallback) {
                call_user_func($this->afterCreatedCallback, $model, $items);
            }
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

    protected function shouldUpdateModel(Model $model, $items): bool
    {
        $callback = $this->canUpdateCallback;

        if (!$callback) {
            return true;
        }

        return $callback($model, $items);
    }

    protected function handleItemsBeforeUpdate(Model $existedModel, array $items): array
    {
        $items = $this->getCustomAttributesToUpdate($existedModel, $items);

        if (!$this->beforeUpdate) {
            return $items;
        }

        $callback = $this->beforeUpdate;

        return collect($items)->filter(fn($value, $attribute) => !$callback($existedModel, $attribute))->toArray();
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

        if(is_array($items) && is_array(Arr::first($items))) {

            return collect($items)->filter(function ($item) use($customAttributesToUpdate) {
                $attribute = $item['attribute'];
                return in_array($attribute, $customAttributesToUpdate);
            })->toArray();
        }

        return collect($items)->filter(fn($value, $attribute) => in_array($attribute, $customAttributesToUpdate))->toArray();
    }

    protected function getModelIfExists(Model $model, array $items): ?Model
    {
        $uniqueFields = array_key_exists($model::class, $this->uniqueFields) ? $this->uniqueFields[$model::class] : [];

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

        $dependencies = collect(Arr::get($this->dependencies, $this->model::class, []))->map(fn($depend) => $this->models[$depend]->id);

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
     *
     * @return mixed
     */
    protected function optimisticUpdate(Model $model, array $items): Model
    {
        $model->update(array_merge($items, ['imported_at' => now()]));

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

    private function getMorphType($model)
    {
        $inverseMorphMap = array_flip(Relation::morphMap());

        return $inverseMorphMap[$this->model::class] ?? $this->model::class;
    }
}
