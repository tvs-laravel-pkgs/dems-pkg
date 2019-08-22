<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashEmployeeDetails extends Model {
	// use SoftDeletes;

	protected $table = 'petty_cash_employee_details';
	public $timestamps = false;

	protected $fillable = [
		'employee_id',
		'total',
		'status',
		'date',
		'created_by',
	];

}