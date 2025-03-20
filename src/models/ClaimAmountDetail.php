<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class ClaimAmountDetail extends Model {
	protected $table = 'claim_amount_details';
	public $timestamps = false;

	protected $fillable = [
		'entity_id',
		'employee_id',
		'claim_amount',
		'status_id',
		'claim_date',
		'created_at',
		'updated_at',
        'claim_reject',
	];
}
