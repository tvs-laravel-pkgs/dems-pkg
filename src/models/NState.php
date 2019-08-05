<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NState extends Model {
	use SoftDeletes;
	protected $table = 'nstates';
	public function country() {
		return $this->belongsTo('App\Country');
	}

	public static function getList($country_id == NULL) {
		if(!$state_id){
			return NState::select('id', 'name')->get();
		}
		else{
			return NState::select('id', 'name')->where('country_id',$country_id)->get();
		}
	}

}
