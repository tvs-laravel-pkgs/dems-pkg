<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalTravel extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'trip_id',
		'mode_id',
		// 'date',
		'from_id',
		'to_id',
		'amount',
		'tax',
		'description',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
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
