<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentFinance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'from_date',
        'to_date',
        'budget_amount',
    ];

    // Relationships --------------------------------------------------------------
    public function department() {
        return $this->belongsTo('Uitoux\EYatra\Department', 'department_id');
    }
}
