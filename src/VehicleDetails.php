<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleDetails extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'employee_id',
        'registration_number',
        'vehicle_user_name',
        'current_km',
        'insurance_company',
        'insurance_expiry_date',
        'insurance_policy_number',
        'rc_chassis_number',
        'vehicle_make_model',
        'vehicle_type',
        'vehicle_fuel_description',
        'vehicle_maker_description',
    ];

    protected $table = 'vehicle_details';

    public $timestamps = true;

    // Relationships --------------------------------------------------------------

    public function employee() {
        return $this->belongsTo('Uitoux\EYatra\Employee');
    }
}
