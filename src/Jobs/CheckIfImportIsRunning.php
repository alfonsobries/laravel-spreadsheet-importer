<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Jobs;

use Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable;
use Alfonsobries\LaravelSpreadsheetImporter\Jobs\CheckIfImportIsRunning;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckIfImportIsRunning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importable;
    protected $logError;
    
    /**
     * Create a new job instance.
     * @param \Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable  $importable
     * @return void
     */
    public function __construct(Importable $importable, $logError = false)
    {
        $this->importable = $importable;
        $this->logError = $logError;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // If the process is finished we are ok
        if ($this->importable->importProcessFinished()) {
            return;
        }

        $seconds = config('laravel-spreadsheet-importer.secs_for_check_if_node_process_still_running');

        // All ok try again in a moment
        if ($this->importable->nodeProcessIsRunning()) {
            CheckIfImportIsRunning::dispatch($this->importable)
                ->delay(now()->addSeconds($seconds));
        } else if (! $this->logError) {
            // If the process is not running dispatch this job again, just to be sure that the 
            // status is not being changed at this moment, the flag ensures the next time the 
            // log added to the importable status
            CheckIfImportIsRunning::dispatch($this->importable, true)
                ->delay(now()->addSeconds($seconds));
        } else {
            // Send the error to the progress handler
            $event = config('laravel-spreadsheet-importer.progress_event');
            event(new $event(
                get_class($this->importable), // relatedClass
                $this->importable->id, // relatedId
                'error', // progressType
                null, // data
                'Process stopped', //
                $this->importable->importable_process_id // processId
            ));
        }
    }
}
