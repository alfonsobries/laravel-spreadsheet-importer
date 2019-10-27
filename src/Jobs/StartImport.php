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

class StartImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importable;
    protected $async;
    protected $settings;

    /**
     * Create a new job instance.
     *
     * @param \Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable  $importable
     * @param array $settings
     * @param boolean $async
     * @return void
     */
    public function __construct(Importable $importable, $settings = [], $async = true)
    {
        $this->importable = $importable;
        $this->settings = $settings;
        $this->async = $async;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $filePath = $this->importable->getFileToImportPath();

        $tableName = $this->importable->getTemporalTableName();

        $process = $this->buildProcess($filePath, $tableName);

        try {
            // When asyn the import will be happening 
            if ($this->async) {
                $process->disableOutput()->start();
                $this->importable->importable_process_id = $process->getPid();
                // When testing the process id that we need is the following one
                // in real production app the process id is updated in the artisan command
                if (app()->environment('testing')) {
                    $this->importable->importable_node_process_id = $process->getPid() + 1;
                }
            } else {
                $process->mustRun();
                $this->importable->importable_output = $process->getOutput();
            }

            $this->importable->importable_status = 'started';
        } catch (ProcessFailedException $exception) {
            $this->importable->importable_feedback = $exception->getMessage();
            $this->importable->importable_status = 'error';
        } catch (\Exception $exception) {
            $this->importable->importable_feedback = $exception->getMessage();
            $this->importable->importable_status = 'error';
        }

        $tableNamePrefix = config('laravel-spreadsheet-importer.temporal_table_name_prefix');

        $this->importable->importable_table_name = $tableNamePrefix . $tableName;
        $this->importable->save();

        $seconds = config('laravel-spreadsheet-importer.secs_for_check_if_node_process_still_running');
        if ($seconds) {
            CheckIfImportIsRunning::dispatchNow($this->importable, $process)
                ->delay(now()->addSeconds($seconds));
        }
    }

    private function buildProcess($filePath, $tableName)
    {
        $settings = array_merge(config('laravel-spreadsheet-importer'), $this->settings);

        $params = [
            $settings['node_path'],
            base_path($settings['importer_path']),

            '--input',
            $filePath,

            '--tableNames',
            $tableName,

            '--prefix',
            $settings['temporal_table_name_prefix'],

            '--sheetsIndex',
            0,

            '--id',
            $settings['id_column'],

            '--relatedId',
            $this->importable->id,

            '--relatedClass',
            sprintf('"\\%s"', get_class($this->importable)),

            '--columns',
            $settings['file_id_column'] . ':' . $this->importable->id,

            '--batchSize',
            $settings['batch_size'],

            '--artisan',
            realpath(base_path('artisan')),

            '--env',
            app()->environment(),

            '--create',

            '--drop',
        ];

        $process = implode(' ', $params);

        return Process::fromShellCommandline($process);
    }
}
