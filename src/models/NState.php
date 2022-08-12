<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NState extends Model {
	use SoftDeletes;
	protected $table = 'nstates';
	protected $fillable = [
		'country_id',
		'code',
		'gstin_state_code',
		'name',
		'axapta_cgst_code',
		'axapta_sgst_code',
		'axapta_igst_code',
		'created_by',
	];
	public function country() {
		return $this->belongsTo('Uitoux\EYatra\NCountry');
	}

	public static function getList($country_id = NULL) {
		// dd($country_id);
		$data = [];
		$option = new NState;
		$option->name = 'Select State';
		$option->id = null;
		// if (!$country_id) {
		// $state_list = NState::select('id', 'name')->get();
		// $data = $state_list->prepend($option);
		// return $data;
		// 	// return NState::select('id', 'name')->get();
		// } else {
		$state_list = NState::select('id', 'name')->where('country_id', $country_id)->get();
		$data = $state_list->prepend($option);
		return $data;
		// return NState::select('id', 'name')->where('country_id', $country_id)->get();
		// }
	}

	public function cities() {
		return $this->hasMany('Uitoux\EYatra\NCity', 'state_id');
	}

	public function operatingState() {
		return $this->hasOne('Uitoux\EYatra\OperatingStates', 'nstate_id');
	}

	public function travelModes() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'state_agent_travel_mode', 'state_id', 'travel_mode_id')->withPivot('agent_id', 'service_charge');
	}

	public function agents() {
		return $this->belongsToMany('Uitoux\EYatra\Agent', 'state_agent_travel_mode', 'state_id', 'agent_id');
	}

}
