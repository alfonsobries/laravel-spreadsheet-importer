<?php

return [
    'node_path' => 'node',
    'importer_path' => './node_modules/.bin/xlsx-laravel-spreadsheet-importer',
    'id_column' => 'data_id',
    'file_id_column' => 'data_file_id',
    'batch_size' => 1000,
    'progress_event' => Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent::class,
    'temporal_table_name_prefix' => 'temp_',
];
