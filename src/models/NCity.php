<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NCity extends Model {
	use SoftDeletes;
	protected $table = 'ncities';

	public function state() {
		return $this->belongsTo('Uitoux\EYatra\NState');
	}

	public static function getList($state_id == NULL) {
		if(!$state_id){
			return NCity::select('id', 'name')->get();
		}
		else{
			return NCity::select('id', 'name')->where('state_id',$state_id)->get();
		}
	}

}
