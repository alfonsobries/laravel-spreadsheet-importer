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
}
