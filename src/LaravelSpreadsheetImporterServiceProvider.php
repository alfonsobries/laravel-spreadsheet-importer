<?php

namespace Alfonsobries\LaravelSpreadsheetImporter;

use Alfonsobries\LaravelSpreadsheetImporter\Console\Commands\ReportImporterProgress;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LaravelSpreadsheetImporterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-spreadsheet-importer.php'),
            ], 'config');

            // Registering package commands.
            $this->commands([
                ReportImporterProgress::class
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-spreadsheet-importer');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-spreadsheet-importer', function () {
            return new LaravelSpreadsheetImporter;
        });

        Event::listen('log.create', 'CompanyName/Events/LogEventsProvider@create');
    }
}
