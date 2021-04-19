<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use R64\ContentImport\Models\File;
use Faker\Generator as Faker;

$factory->define(File::class, function (Faker $faker) {
    return [
        'url' => $faker->filePath(),
        'disk' => 's3',
        'processed_at' => now()
    ];
});
