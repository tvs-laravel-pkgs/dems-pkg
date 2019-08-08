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
		// 'check_in_date',
		// 'checkout_date',
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

	public function city() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'city_id');
	}

	public function stateType() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'stay_type_id');
	}

	public function attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3181)->where('attachment_type_id', 3200);
	}
}
