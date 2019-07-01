<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ImporterProgressEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
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

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
