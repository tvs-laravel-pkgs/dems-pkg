<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperatingStates extends Model
{
     use SoftDeletes;

    protected $table = 'operating_states';
    public $timestamps = false;

    protected $fillable = [
        // 'id',
        'company_id',
        'nstate_id',
        'gst_number',
        'legal_name',
        'address',
        'pincode',
        'created_by',
    ];
}
