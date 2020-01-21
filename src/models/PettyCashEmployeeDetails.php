<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashEmployeeDetails extends Model {
	// use SoftDeletes;

	protected $table = 'petty_cash_employee_details';
	public $timestamps = false;

	protected $fillable = [
		// 'id',
		'petty_cash_id',
		'expence_type',
		'petty_cash_type',
		'date',
		'purpose_id',
		'travel_mode_id',
		'from_place',
		'to_place',
		'from_km',
		'to_km',
		'amount',
		'tax',
		'remarks',
		'details',
		'created_by',
	];
}