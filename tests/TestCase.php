<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Tests;

use Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent;
use Alfonsobries\LaravelSpreadsheetImporter\Listeners\ImporterProgressEventListener;
use Dotenv\Dotenv;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        
        $this->setUpEventListeners($this->app);
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $dotenv = Dotenv::create(__DIR__.'/../');
        $dotenv->load();
        
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => env('DB_SCHEMA', 'public'),
            'sslmode' => 'prefer',
        ]);

        $app->setBasePath(__DIR__.'/..');

        $app['config']->set('laravel-spreadsheet-importer', require(__DIR__.'/../config/config.php'));
    }

    /**
     * Set up the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        Schema::dropAllTables();
        Schema::create('my_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('importable_process_id')->nullable();
            $table->string('importable_node_process_id')->nullable();
            $table->string('importable_table_name')->nullable();
            $table->string('importable_total_rows')->nullable();
            $table->string('importable_processed')->nullable();
            $table->string('importable_status')->default('new');
            $table->mediumText('importable_output')->nullable();
            $table->mediumText('importable_feedback')->nullable();
        });
    }

    /**
     * Set up the event listeners.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpEventListeners($app)
    {
        $this->app['events']->listen(ImporterProgressEvent::class, ImporterProgressEventListener::class);
    }
}