<?php

namespace R64\ContentImport;

use R64\ContentImport\Commands\ImportFilesCommand;
use R64\ContentImport\Commands\ProcessImportedFilesCommand;
use R64\ContentImport\ContentImport as ImportClass;
use Illuminate\Support\ServiceProvider;

class ContentImportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
         $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

         $this->commands([
             ImportFilesCommand::class,
             ProcessImportedFilesCommand::class
         ]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('content_import.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'migrations');
        }

        $this->app->make('Illuminate\Database\Eloquent\Factory')->load(__DIR__ . '/../database/factories');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'content-import');

        // Register the main class to use with the facade
        $this->app->singleton('content-import', function () {
            return $this->app->make(ImportClass::class);
        });
    }
}
