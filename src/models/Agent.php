<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model {
	use SoftDeletes;

	protected $fillable = [
		'company_id',
		'code',
		'name',
		'created_by',
	];

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function travelModes() {
		return $this->belongsToMany('App\Entity', 'agent_travel_mode', 'agent_id', 'travel_mode_id');
	}
	public function address() {
		return $this->hasOne('Uitoux\EYatra\Address', 'entity_id')->where('address_of_id', 3161);
	}

}
