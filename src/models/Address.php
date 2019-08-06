<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class Address extends Model {
	protected $table = 'ey_addresses';
	public $timestamps = false;

	protected $fillable = [
		'address_of_id',
		'entity_id',
		'name',
		'line_1',
		'country_id',
		'state_id',
		'city_id',
		'pincode',
	];

}
