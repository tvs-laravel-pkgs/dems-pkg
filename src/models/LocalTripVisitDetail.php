<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalTripVisitDetail extends Model {
	use SoftDeletes;
	protected $table = 'local_trip_visit_details';

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\LocalTrip', 'trip_id');
	}

	public function getTravelDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}

}
