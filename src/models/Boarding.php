<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boarding extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'trip_id',
		'city_id',
		'expense_name',
		// 'date',
		'amount',
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

	public function attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3182)->where('attachment_type_id', 3200);
	}

}
