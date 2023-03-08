<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LodgingShareDetail extends Model {
	use SoftDeletes;
	protected $table = 'lodging_share_details';

	protected $fillable = [
		'lodging_id',
		'employee_id',
	];

	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}
}
