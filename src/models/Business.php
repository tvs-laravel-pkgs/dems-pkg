<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\BusinessFinance;

class Business extends Model
{
    use SoftDeletes;
    protected $table = 'businesses';
    protected $fillable = [
        'name',
        'company_id',
        'short_name',

    ];
    public function getFromDateAttribute($value) {
        return !empty($value) ? date('d-m-Y', strtotime($value)) : '';
    }

    public function getToDateAttribute($value) {
        return !empty($value) ? date('d-m-Y', strtotime($value)) : '';
    }
    public function businessFinance() {
        return $this->hasMany('Uitoux\EYatra\BusinessFinance', 'business_id');
    }
    public function department() {
        return $this->hasMany('Uitoux\EYatra\Department', 'business_id');
    }
    
}
