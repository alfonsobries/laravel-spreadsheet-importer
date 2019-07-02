# Laravel Faster Spreadsheet Importer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alfonsobries/laravel-spreadsheet-importer.svg?style=flat-square)](https://packagist.org/packages/alfonsobries/laravel-spreadsheet-importer)

This package works together with the [xlsx-laravel-spreadsheet-importer](https://github.com/alfonsobries/xlsx-laravel-spreadsheet-importer) CLI tool to quickly import spreadsheets from xlsx files an other [compatible formats](https://www.npmjs.com/package/xlsx#file-formats) and then store the values into temporal database tables that are way easier to work with.

Because we use Node instead of PHP we can import larger xlsx files in just a few seconds, and because we will work with a SQL table the operations with the data are **considerably** faster.

- Includes a command that expect the data according to the cli tool and trigger an observable event with the progress of the import.
- Adds a Trait that add the ability to your model to work with the temporal data as easy as any Eloquent relationship.
- Once the import is finished triggers another observable event so you can know when you can continue with the following operations related with your import.
- Manages multiple files format, see (https://www.npmjs.com/package/xlsx#file-formats)
- Compatible with PostgreSQL as MySQL

## Installation

You can install the package via composer:

```bash
composer require alfonsobries/laravel-spreadsheet-importer
```

You also will need the npm package ([check alfonsobries/xlsx-laravel-spreadsheet-importer for more info](https://github.com/alfonsobries/xlsx-laravel-spreadsheet-importer))

``` bash
npm install @alfonsobries/xlsx-laravel-spreadsheet-importer --save
```

Optionally publish the config files by running:
```bash
php artisan vendor:publish --provider="Alfonsobries\LaravelSpreadsheetImporter\LaravelSpreadsheetImporterServiceProvider" --tag="config"
``` 

## Configure your model

The files that you will import usually will be associated to one Model, also your Model will be used to store the progress of the import.

Start by adding the `InteractsWithImporter` trait and the `Importable` contract to the Model.

You will also need to define the methods in the `Importable` contract

```php
<?php

namespace App\Models;

use Alfonsobries\LaravelSpreadsheetImporter\Traits\InteractsWithImporter;
use Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable;
use Illuminate\Database\Eloquent\Model;

class MyModel extends Model implements Importable
{
    use InteractsWithImporter;

    /**
     * Return the full path of the file that will be imported
     * @return string
     */
    public function getFileToImportPath() {
        // Notice that this line should be adapted to your application, this is an example for
        // a path that comes from a file that was stored using the spatie media library package
        return $this->getFirstMedia('file')->getPath();
    }

    /**
     * Return the temporaly table name that will be used to store the spreadsheet contents
     *
     * @return string
     */
    public function getTemporalTableName() {
        // This is an example you should adapt this line to your own application
        // You should create a method that always return the same value for the same model
        return sprintf('file_%s', $this->id);
    }
```

### Add the neccesary columns to the `Importable` Model

Your Model will need a few columns to store the progress and status of the import, to add those columns you can create a migration like the following (just change the table name for your model table name):
``` php
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImportableColumnsToModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my_model_table', function (Blueprint $table) {
            $table->string('importable_process_id')->nullable();
            $table->string('importable_node_process_id')->nullable();
            $table->string('importable_table_name')->nullable();
            $table->string('importable_total_rows')->nullable();
            $table->string('importable_processed')->nullable();
            $table->string('importable_status')->default('new');
            $table->mediumText('importable_output')->nullable();
            $table->mediumText('importable_process_exception')->nullable();
            $table->mediumText('importable_exception')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('my_model_table', function (Blueprint $table) {
            $table->dropColumn('importable_process_id');
            $table->dropColumn('importable_node_process_id');
            $table->dropColumn('importable_table_name');
            $table->dropColumn('importable_total_rows');
            $table->dropColumn('importable_processed');
            $table->dropColumn('importable_status');
            $table->dropColumn('importable_output');
            $table->dropColumn('importable_process_exception');
            $table->dropColumn('importable_exception');
        });
    }
}
``` 

Once the Model is configured and you import a spreadsheet you can interact with the data stored in the temporal table using the `tempData()` relationship.

For example lets say that your spreadsheet has a sales column and you want the total:

```php
$totalSales = $myModel->tempData()->sum('sales');
``` 

## Import a file

For importing a file you will need to call the `xlsx-laravel-spreadsheet-importer` command using the instructions [here](https://github.com/alfonsobries/xlsx-laravel-spreadsheet-importer) but to make your work easy this package includes a `StartImport` Job that will build the command for you (dont forget to add the neccesary columns to store the progres to your model).

You can dispatch the job at any moment, it receive the `importable` Model as a param.

For example, you can call the job once you save your model in a `store` method in a controller (assuming that your model is associating a file and will return a valid file_path in the `getFileToImportPath()` method):

``` php 
public function store(Request $request, MyModel $model)
{
    $model->addFile($request->file);

    \Alfonsobries\LaravelSpreadsheetImporter\Jobs\StartImport::dispatch($model);

    // ...The rest of the code
}
```

That job will create and trigger the Node command, then the Node command will call the `ReportImporterProgress` artisan command every time has some progress to inform, finally the artisan command will trigger a `ImporterProgressEvent` that you can listen with your custom event listener or by adding `ImporterProgressEventListener` that comes in this package, that listener is responsible to store the progress of your import into your Model:

To configure the event listener add the following lines into your `app/Providers/EventServiceProvider.php`:

``` php 
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // Every time the importer has some progress
        \Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent => [
            // (Or use your own event listener)
            \Alfonsobries\LaravelSpreadsheetImporter\Listeners\ImporterProgressEventListener::class,
        ],

        // The `ImporterProgressEventListener` also creates the following observable events:
        // When the import finished
        \Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressFinishedEvent => [
            // In this case you will need to define your own event listener 
        ],

        // When the import reports an error:
        \Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressErrorEvent => [
            // In this case you will need to define your own event listener 
        ],
    ];
    // ...
``` 

## Testing

For running the tests you will need to install the dependencies and have a valid testing database

+ Install the composer packages `composer install`
+ Because this package interacts with a database you will need to define a `.env` with a valid mysql or postgresql database data (use `env.example` as an example)
+ Also you need to install the npm dependency `npm install`

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email alfonso@vexilo.com instead of using the issue tracker.

## Credits

- [Alfonso Bribiesca](https://github.com/alfonsobries)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
