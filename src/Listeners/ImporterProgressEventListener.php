<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Listeners;

use Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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

        $model = $relatedClass::find($relatedId);
        
        $model->importable_status = $progressType;

        switch ($progressType) {
            case 'error':
                $model->importable_error_message = $message;
                break;
            case 'total_rows':
                $model->importable_total_rows = $data;
                break;
            case 'table_created':
                $model->importable_table_name = $data;
                break;
            case 'processing':
                $model->importable_processed = $data;
                break;
        }

        $model->save();

        if ($model->importable_status === 'finished') {
            info("finished");
        }
    }
}
