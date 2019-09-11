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

	public static function create($address_of_id, $entity, $faker, $company_id) {
		$address = Self::firstOrNew([
			'address_of_id' => $address_of_id,
			'entity_id' => $entity->id,
		]);
		$address->name = 'Primary';
		$address->line_1 = $faker->streetAddress;
		$country = NCountry::where('company_id', $company_id)->select('id')->first();
		$state = $country->states()->inRandomOrder()->first();
		$city = $state->cities()->inRandomOrder()->first();
		$address->city_id = $city->id;
		$address->save();

		return $address;
	}

}
