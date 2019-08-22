<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCash extends Model {
	use SoftDeletes;
	protected $table = 'petty_cash';

	protected $fillable = [
		'id',
		'petty_cash_employee_details_id',
		'expence_type',
		'date',
		'purpose_id',
		'travel_mode_id',
		'from_place',
		'to_place',
		'from_KM_reading',
		'to_KM_reading',
		'amount',
		'tax',
		'details',
		'created_by',
	];

}