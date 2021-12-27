<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;
    protected $table = 'departments';
    protected $fillable = [
        'company_id',
        'business_id',
        'name',
        'code',
        'short_name',

    ];
    public function getFromDateAttribute($value) {
        return !empty($value) ? date('d-m-Y', strtotime($value)) : '';
    }

    public function getToDateAttribute($value) {
        return !empty($value) ? date('d-m-Y', strtotime($value)) : '';
    }
    public function business() {
        return $this->belongsTo('Uitoux\EYatra\Business', 'business_id');
    }
    public function departmentFinance() {
        return $this->hasMany('Uitoux\EYatra\DepartmentFinance', 'department_id');
    }
}
