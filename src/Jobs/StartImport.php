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

class StartImport
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;
    protected $async;
    protected $settings;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Importable $model, $settings = [], $async = true)
    {
        $this->model = $model;
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
        $filePath = $this->model->getFileToImportPath();

        $tableName = $this->model->getTemporalTableName();

        $process = $this->buildProcess($filePath, $tableName);

        try {
            // When asyn the import will be happening 
            if ($this->async) {
                $process->disableOutput()->start();
                $this->model->importable_process_id = $process->getPid();
                // When testing the process id that we need is the following one
                // in real production app the process id is updated in the artisan command
                if (app()->environment('testing')) {
                    $this->model->importable_node_process_id = $process->getPid() + 1;
                }
            } else {
                $process->mustRun();
                $this->model->importable_output = $process->getOutput();
            }

            $this->model->importable_status = 'started';
        } catch (ProcessFailedException $exception) {
            $this->model->importable_process_exception = $exception->getMessage();
            $this->model->importable_status = 'error';
        } catch (\Exception $exception) {
            $this->model->importable_exception = $exception->getMessage();
            $this->model->importable_status = 'error';
        }

        $tableNamePrefix = config('laravel-spreadsheet-importer.temporal_table_name_prefix');
        $this->model->importable_table_name = $tableNamePrefix . $tableName;
        $this->model->save();
    }

    private function buildProcess($filePath, $tableName)
    {
        $settings = array_merge(config('laravel-spreadsheet-importer'), $this->settings);

        $params = [
            $settings['node_path'],
            $settings['importer_path'],
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
            $this->model->id,

            '--relatedClass',
            sprintf('"\\%s"', get_class($this->model)),

            '--columns',
            $settings['file_id_column'] . ':' . $this->model->id,

            '--batchSize',
            $settings['batch_size'],

            '--artisan',
            base_path('artisan'),

            '--env',
            app()->environment(),

            '--create',

            '--drop',

            ' &',
        ];

        return new Process(implode(' ', $params));
    }
}
