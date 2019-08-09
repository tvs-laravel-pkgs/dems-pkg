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
		'line_2',
		// 'country_id',
		// 'state_id',
		'city_id',
		'pincode',
	];

	public function city() {
		return $this->belongsTo('Uitoux\EYatra\NCity');
	}

	public static function create($address_of_id, $entity, $faker) {
		$address = Address::firstOrNew([
			'address_of_id' => $address_of_id,
			'entity_id' => $entity->id,
		]);
		$address->name = 'Primary';
		$address->line_1 = $faker->streetAddress;
		$country = NCountry::find(5);
		$state = $country->states()->inRandomOrder()->first();
		$city = $state->cities()->inRandomOrder()->first();
		// $address->country_id = $country->id;
		// $address->state_id = $state->id;
		$address->city_id = $city->id;
		$address->save();

		return $address;
	}

}
