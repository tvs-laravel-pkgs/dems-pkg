<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeClaim extends Model {
	use SoftDeletes;
	protected $table = 'ey_employee_claims';

	protected $fillable = [
		'employee_id',
		'trip_id',
		'total_amount',
		'status_id',
		'payment_id',
		'is_deviation',
	];
}
