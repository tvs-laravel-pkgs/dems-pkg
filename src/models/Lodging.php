<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lodging extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'trip_id',
		'city_id',
		'check_in_date',
		'checkout_date',
		'stayed_days',
		'stay_type_id',
		'amount',
		'tax',
		'total',
		'reference_number',
		'description',
		'lodge_name',
		'eligible_amount',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
	}

}
