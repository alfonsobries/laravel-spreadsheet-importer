<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Events;

use Alfonsobries\LaravelSpreadsheetImporter\Contracts\Importable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImporterProgressFinishedEvent
{
    use Dispatchable, SerializesModels;

    public $importable;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Importable $importable)
    {
        $this->importable = $importable;
    }
}
