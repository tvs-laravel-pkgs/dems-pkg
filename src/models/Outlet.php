<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model {
	use SoftDeletes;

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public static function getList() {
		return Outlet::select('id', 'name')->get();
	}

	public function address() {
		return $this->hasOne('Uitoux\EYatra\Address', 'entity_id')->where('address_of_id', 3160);
	}

}
