<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use R64\ContentImport\Models\File;
use Faker\Generator as Faker;

$factory->define(File::class, fn(Faker $faker) => [
    'url' => $faker->filePath(),
    'disk' => 's3',
    'processed_at' => now()
]);
