<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    "directory" => env('IMPORT_DIRECTORY', 'imports'),

    "chunck_size" => env('IMPORT_CHUNCK_SIZE', 1000),

    "heading_row" => 1
];
