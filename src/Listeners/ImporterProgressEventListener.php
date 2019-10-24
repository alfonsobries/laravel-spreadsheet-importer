<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Listeners;

use Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent;
use Alfonsobries\LaravelSpreadsheetImporter\Jobs\HandleProgress;

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
        HandleProgress::dispatch(
            $event->relatedClass,
            $event->relatedId,
            $event->progressType,
            $event->data,
            $event->message,
            $event->pid
        );
    }
}
