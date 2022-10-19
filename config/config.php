<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    /**
     * s3 bucket directory to import the files from
     */
    'directory' => 'imports',

    'chunck_size' => 1000,

    'heading_row' => true,

    /**
     * allowed extensions to process
     */
    'extensions' => ['.csv'],

    'supported_delimiters' => [';', ',', '|', '\t'],

    /**
     * Model for saving imported files.
     */
    'model' => \R64\ContentImport\Models\File::class

];
