<?php

namespace Alfonsobries\LaravelSpreadsheetImporter;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Alfonsobries\LaravelSpreadsheetImporter\Skeleton\SkeletonClass
 */
class LaravelSpreadsheetImporterFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-spreadsheet-importer';
    }
}
