<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model {
	use SoftDeletes;

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function trips() {
		return $this->hasMany('Uitoux\EYatra\Trip');
	}

	public function grade() {
		return $this->belongsTo('Uitoux\EYatra\Entity');
	}

	public function outlet() {
		return $this->belongsTo('Uitoux\EYatra\Outlet');
	}

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('user_type_id', 3121);
	}

	public static function getList() {
		return Employee::select('id', 'name')->get();
	}

}
