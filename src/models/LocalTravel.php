<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalTravel extends Model {
	use SoftDeletes;

	protected $fillable = [
		// 'id',
		'trip_id',
		'mode_id',
		'city_id',
		// 'date',
		'from',
		'to',
		'amount',
		'tax',
		'eligible_amount',
		'description',
		'attachment_status',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function city() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'city_id');
	}

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
	}

	public function getDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
	public function getAttachmentStatusAttribute($val) {
		return (!empty($val) && $val == 1) ? 'Yes' : 'No';
	}
	public function setAttachmentStatusAttribute($val) {
		$this->attributes['attachment_status'] = (!empty($val) && $val == 'Yes') ? 1 : 0;
	}

	public function fromCity() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'from_id');
	}

	public function toCity() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'to_id');
	}

	public function travelMode() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'mode_id');
	}

	public function attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3183)->where('attachment_type_id', 3200);
	}

}
