<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boarding extends Model {
	use SoftDeletes;

	protected $fillable = [
		// 'id',
		'trip_id',
		'city_id',
		'boarding_type_id',
		'expense_name',
		// 'date',
		'amount',
		'tax',
		'days',
		'eligible_amount',
		'remarks',
		'attachment_status',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
	}
	public function getFromDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
	public function getToDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
	public function getAttachmentStatusAttribute($val) {
		return (!empty($val) && $val == 1) ? 'Yes' : 'No';
	}
	public function setAttachmentStatusAttribute($val) {
		$this->attributes['attachment_status'] = (!empty($val) && $val == 'Yes') ? 1 : 0;
	}

	public function city() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'city_id');
	}
	public function stateType() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'boarding_type_id');
	}

	public function attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3182)->where('attachment_type_id', 3200);
	}
	public function pending_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3182)->where('attachment_type_id', 3200)->where('view_status', 0);
	}

}
