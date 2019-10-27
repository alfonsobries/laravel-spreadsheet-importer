<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Traits;

use Alfonsobries\LaravelSpreadsheetImporter\Models\TempData;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithImporter
{
    /**
     * The Model has many temporal data
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tempData()
    {
        return $this->hasMany(TempData::class, config('laravel-spreadsheet-importer.file_id_column'));
    }

    /**
     * Overrides the original newRelatedInstance to use the `table_name` as a dynamic table for this
     * relationship
     *
     * @param  string  $class
     * @return mixed
     */
    protected function newRelatedInstance($class)
    {
        if ($class === TempData::class) {
            $model = new TempData;

            // Use the table name used as a temporal table
            $model->setTable($this->importable_table_name);

            return tap($model, function ($instance) {
                if (! $instance->getConnectionName()) {
                    $instance->setConnection($this->connection);
                }
            });
        }

        return parent::newRelatedInstance($class);
    }

    /**
     * If the php script is running
     * 
     * @return boolean
     */
    public function nodeProcessIsRunning()
    {
        if (! $this->importable_process_id) {
            return false;
        }

        if (app()->environment('testing')) {
            // When testing the process doesnt disapear but has process state code of `R` (running
            // or runnable (on run queue)). This condition may work even when no testing but in my
            // tests seems like an unnecesary extra condition that can cause troubles in production
            return posix_kill($this->importable_process_id, 0)
                && trim(shell_exec("ps aux | grep 'node' | grep '" . $this->importable_process_id . "' | grep -v 'ps aux' | awk '{print $8}'")) === 'R+';
        }

        return posix_kill($this->importable_process_id, 0);
    }

    /**
     * Cancel the process related with the import
     *
     * @return self
     */
    public function cancel()
    {
        if ($this->nodeProcessIsRunning()) {
            posix_kill($this->importable_process_id, 9);
        }
        
        $this->importable_status = 'canceled';
        
        $this->save();

        return $this;
    }
}
