<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class ImporterProgressEvent
{
    use Dispatchable, SerializesModels;
    
    public $relatedId;
    public $progressType;
    public $data;
    public $message;
    public $pid;
    public $relatedClass;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($relatedClass, $relatedId, $progressType, $data, $message, $pid)
    {
        $this->relatedClass = $relatedClass;
        $this->relatedId = $relatedId;
        $this->progressType = $progressType;
        $this->data = $data;
        $this->message = $message;
        $this->pid = $pid;
    }
}
