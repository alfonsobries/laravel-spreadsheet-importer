<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Models;

use Illuminate\Database\Eloquent\Model;

class TempData extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $primaryKey = 'data_id';
}
