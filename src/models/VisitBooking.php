<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitBooking extends Model {
	use SoftDeletes;

	public function visit() {
		return $this->belongsTo('Uitoux\EYatra\Visit');
	}

}
