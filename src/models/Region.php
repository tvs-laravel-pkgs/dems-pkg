<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model {
	use SoftDeletes;
	protected $table = 'regions';

	protected $fillable = [
		'id',
		'name',
		'code',
		'state_id',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function state() {
		return $this->belongsTo('Uitoux\EYatra\NState', 'state_id');
	}
}
