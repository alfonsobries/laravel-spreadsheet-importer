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
     * If the node script is running
     * 
     * @return boolean
     */
    public function nodeScriptIsRunning()
    {
        return $this->importable_node_process_id && posix_kill($this->importable_node_process_id, 0);
    }

    /**
     * If the php script is running
     * 
     * @return boolean
     */
    public function commandScriptIsRunning()
    {
        return $this->importable_process_id && posix_kill($this->importable_process_id, 0);
    }

    /**
     * Cancel the process related with the import
     *
     * @return self
     */
    public function cancel()
    {
        if ($this->nodeScriptIsRunning()) {
            posix_kill($this->importable_node_process_id, 9);
        }

        if ($this->commandScriptIsRunning()) {
            posix_kill($this->importable_process_id, 9);
        }
        
        $this->importable_status = 'canceled';
        
        $this->save();

        return $this;
    }
}
