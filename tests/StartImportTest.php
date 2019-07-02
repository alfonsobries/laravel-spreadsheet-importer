<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Tests;

use Alfonsobries\LaravelSpreadsheetImporter\Jobs\StartImport;
use Alfonsobries\LaravelSpreadsheetImporter\LaravelSpreadsheetImporter;
use Alfonsobries\LaravelSpreadsheetImporter\Tests\stubs\Models\MyModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

class StartImportTest extends TestCase
{
    /** @test */
    public function it_creates_the_import_command_and_imports_the_file()
    {
        $importable = MyModel::create();

        $job = new StartImport($importable, [], false);

        $job->handle();

        $this->assertTrue(Schema::hasTable($importable->importable_table_name));

        $this->assertEquals(19, $importable->tempData()->count());

        $this->assertEquals(
            config('laravel-spreadsheet-importer.temporal_table_name_prefix'). $importable->getTemporalTableName(),
            $importable->importable_table_name
        );
    }
}

