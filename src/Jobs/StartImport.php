<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Jobs;

use Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable;
use Alfonsobries\LaravelSpreadsheetImporter\Jobs\CheckIfImportIsRunning;
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

                $this->importable->importable_process_id = $this->getNodeCommandProcessId($process);
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
        if ($seconds && config('queue.default') !== 'sync') {
            CheckIfImportIsRunning::dispatch($this->importable)
                ->delay(now()->addSeconds($seconds));
        }
    }

    /**
     * Apparently the symfony library is creating two process the first to start the command and the
     * second is the real job, we care about the second PID.
     * 
     * @return Number
     */
    private function getNodeCommandProcessId(Process $process)
    {
        // When testing the PID works differently, thw following command looks for the process
        // id using the description of the command
        if (app()->environment('testing')) {
            return intval(trim(shell_exec("ps aux | grep 'node' | grep 'laravel-spreadsheet-importer' | grep -v 'ps aux' | awk '{print $2}'")));
        }

        return $process->getPid() + 1;
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
