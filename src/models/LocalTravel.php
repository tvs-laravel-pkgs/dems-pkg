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
		'date',
		'from_id',
		'to_id',
		'amount',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
	}

}
