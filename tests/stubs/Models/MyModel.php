<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Tests\stubs\Models;

use Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable;
use Alfonsobries\LaravelSpreadsheetImporter\Traits\InteractsWithImporter;
use Illuminate\Database\Eloquent\Model;

class MyModel extends Model implements Importable
{
    use InteractsWithImporter;

    public $timestamps = false;

    public $file = 'small.csv';

    public function getFileToImportPath()
    {
        return __DIR__ . '/../' . $this->file;
    }

    public function getTemporalTableName()
    {
        return sprintf('data_%s', $this->id);
    }
}