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
		'gstin',
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

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('user_type_id', 3122);
	}

	public function bankDetail() {
		return $this->hasOne('Uitoux\EYatra\BankDetail', 'entity_id');
	}

	public function walletDetail() {
		return $this->hasOne('Uitoux\EYatra\WalletDetail', 'entity_id');
	}

}
