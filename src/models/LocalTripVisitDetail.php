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

	public function travelMode() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'travel_mode_id');
	}

	public function expenseAttachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3186)->where('attachment_type_id', 3200);
	}
}
