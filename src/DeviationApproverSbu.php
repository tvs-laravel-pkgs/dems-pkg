<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviationApproverSbu extends Model
{
    use SoftDeletes;
    protected $table = 'deviation_approver_sbus';
    protected $fillable = [
        'deviation_approver_id',
        'sbu_id',
        'created_by',
    ];
    public function deviationApprover() {
        return $this->belongsTo('Uitoux\EYatra\DeviationApprover', 'deviation_approver_id');
    }

    public function sbu() {
        return $this->belongsTo('Uitoux\EYatra\SBU', 'sbu_id');
    }
}
