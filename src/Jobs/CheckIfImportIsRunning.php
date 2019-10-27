<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Jobs;

use Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CheckIfImportIsRunning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importable;
    protected $process;

    /**
     * Create a new job instance.
     * @param \Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable  $importable
     * @param \Symfony\Component\Process\Process  $process
     * @return void
     */
    public function __construct(Importable $importable, Process $process)
    {
        $this->importable = $importable;
        $this->process = $process;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // If the process is finished we are ok
        if ($this->importable->importable_status === 'success' || $this->importable->importable_status === 'error') {
            return;
        }

        // All ok try again in a moment
        if ($this->process->isRunning()) {
            $seconds = config('laravel-spreadsheet-importer.secs_for_check_if_node_process_still_running');
            CheckIfImportIsRunning::dispatchNow($this->importable, $this->process)
                ->delay(now()->addSeconds($seconds));
        } else {
            $this->importable->importable_feedback = 'Process stoped';
            $this->importable->importable_status = 'error';
            $this->importable->save();
        }
    }
}
