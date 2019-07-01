<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Console\Commands;

use Illuminate\Console\Command;

class ReportImporterProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importer:progress {--relatedClass=} {--relatedId=} {--type=} {--data=} {--message=} {--pid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receive the progress of an import';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (app()->environment('testing')) {
            info(
                $this->option('relatedClass') . '::'
                . $this->option('relatedId') . '::'
                . $this->option('type') . '::'
                . $this->option('data', null) . '::'
                . $this->option('message', null) . '::'
                . $this->option('pid', null)
            );
            return;
        }

        $event = config('laravel-spreadsheet-importer.progress_event');
        
        event(new $event(
            $this->option('relatedClass'),
            $this->option('relatedId'),
            $this->option('type'),
            $this->option('data', null),
            $this->option('message', null),
            $this->option('pid', null)
        ));
    }
}
