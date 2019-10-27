<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleProgress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $relatedClass;
    protected $relatedId;
    protected $progressType;
    protected $data;
    protected $message;
    protected $processId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($relatedClass, $relatedId, $progressType, $data, $message, $processId)
    {
        $this->relatedClass = $relatedClass;
        $this->relatedId = $relatedId;
        $this->progressType = $progressType;
        $this->data = $data;
        $this->message = $message;
        $this->processId = $processId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $importable = $this->relatedClass::find($this->relatedId);
        
        $importable->importable_status = $this->progressType;

        switch ($this->progressType) {
            case 'error':
                $importable->importable_feedback = $this->message;
                break;
            case 'total_rows':
                $importable->importable_total_rows = $this->data;
                break;
            case 'table_created':
                $importable->importable_table_name = $this->data;
                break;
            case 'processing':
                $importable->importable_processed = $this->data;
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
