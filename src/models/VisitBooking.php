<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitBooking extends Model {
	use SoftDeletes;

	protected $fillable = [
		'visit_id',
		'type_id',
		'travel_mode_id',
		'reference_number',
		'amount',
		'tax',
		'service_charge',
		'total',
		'claim_amount',
		'payment_status_id',
		'payment_id',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function visit() {
		return $this->belongsTo('Uitoux\EYatra\Visit');
	}

}
