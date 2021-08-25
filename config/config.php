<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'directory' => 'imports',

    'chunck_size' => 1000,

    'heading_row' => true,

    'extensions' => ['.csv'],

    'csv' => \R64\ContentImport\Processors\CsvProcessor::class,

    'xlsx' => \R64\ContentImport\Processors\CsvProcessor::class,

    'txt' => \R64\ContentImport\Processors\TxtProcessor::class
];
