<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingMethod extends Model {
	use SoftDeletes;

	protected $fillable = [
		'name',
		'travel_type_id',
	];
}
