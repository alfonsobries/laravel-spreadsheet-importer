<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Listeners;

use Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent;

class ImporterProgressEventListener
{
    /**
     * Handle the event.
     *
     * @param  ImporterProgress  $event
     * @return void
     */
    public function handle(ImporterProgressEvent $event)
    {
        $relatedClass = $event->relatedClass;
        $relatedId = $event->relatedId;
        $progressType = $event->progressType;
        $data = $event->data;
        $message = $event->message;
        $pid = $event->pid;

        $importable = $relatedClass::find($relatedId);
        
        $importable->importable_status = $progressType;

        switch ($progressType) {
            case 'error':
                $importable->importable_error_message = $message;
                break;
            case 'total_rows':
                $importable->importable_total_rows = $data;
                break;
            case 'table_created':
                $importable->importable_table_name = $data;
                break;
            case 'processing':
                $importable->importable_processed = $data;
                break;
        }

        $importable->save();

        if ($importable->importable_status === 'finished') {
            $event = config('laravel-spreadsheet-importer.finished_event');
            event(new $event($importable));
        }

        if ($importable->importable_status === 'error') {
            $event = config('laravel-spreadsheet-importer.error_event');
            event(new $event($importable));
        }
    }
}
