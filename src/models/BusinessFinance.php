<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Business;


class BusinessFinance extends Model
{
    use SoftDeletes;
    protected $table = 'business_finances';

    protected $fillable = [
        'business_id',
        'from_date',
        'to_date',
        'budget_amount',
    ];

    // Relationships --------------------------------------------------------------

    public function business() {
        return $this->belongsTo('Uitoux\EYatra\Business', 'business_id');
    }
}
