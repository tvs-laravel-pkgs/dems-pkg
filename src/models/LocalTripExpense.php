<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalTripExpense extends Model {
	use SoftDeletes;
	protected $table = 'local_trip_expenses';

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\LocalTrip', 'trip_id');
	}

	public function getExpenseDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}

	public function expenseAttachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3188)->where('attachment_type_id', 3200);
	}
}
