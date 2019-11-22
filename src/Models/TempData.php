<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Models;

use Illuminate\Database\Eloquent\Model;

class TempData extends Model
{
    const STATUS_FINISHED = 'finished';
    const STATUS_ERROR = 'error';
    const STATUS_CANCELED = 'canceled';
    const STATUS_STARTED = 'started';

    public $timestamps = false;
    protected $guarded = [];
    protected $primaryKey = 'data_id';

    public static function finishedStatuses()
    {
        return [
            self::STATUS_FINISHED,
            self::STATUS_ERROR,
            self::STATUS_CANCELED,
        ];
    }
}
