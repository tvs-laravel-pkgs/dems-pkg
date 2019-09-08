<?php

namespace Uitoux\EYatra;

use Auth;
use Illuminate\Database\Eloquent\Model;

class NCountry extends Model {

	protected $table = 'countries';

	protected $fillable = [
		'id',
		'name',
		'code',
	];

	public static function getList() {
		$data = [];
		$option = new NCountry;
		$option->name = 'Select Country';
		$option->id = null;
		$countries_list = NCountry::select('name', 'id')->where('company_id', Auth::user()->company_id)->get();
		$data = $countries_list->prepend($option);
		return $data;
		// return NCountry::select('id', 'name')->get();
	}

	public function states() {
		return $this->hasMany('Uitoux\EYatra\NState', 'country_id');
	}

	static public function create($countries, $admin, $company) {

		foreach ($countries as $country_id => $country_data) {
			$country = NCountry::firstOrNew([
				'id' => $country_id,
				'company_id' => $company->id,
			]);
			$country->fill($country_data['data']);
			$country->save();
			foreach ($country_data['states'] as $state_code => $state_data) {
				$state = NState::firstOrNew([
					'country_id' => $country->id,
					'code' => $state_code,
				]);
				$state->fill($state_data['data']);
				$state->created_by = $admin->id;
				$state->save();
				foreach ($state_data['regions'] as $region_code => $region_name) {
					$region = Region::firstOrNew([
						'company_id' => $company->id,
						'state_id' => $state->id,
						'code' => $region_code,
						'name' => $region_name,
					]);
					$region->created_by = $admin->id;
					$region->save();
				}
				foreach ($state_data['cities'] as $city_name) {
					$city = NCity::firstOrNew([
						'state_id' => $state->id,
						'name' => $city_name,
						'company_id' => $company->id,
					]);
					$city->created_by = $admin->id;
					$city->save();
				}
			}
		}

	}

	static public function createDummies($admin) {
		//COUNTRIES
		for ($i = 5; $i <= 6; $i++) {
			$country = NCountry::find($i);
			if ($country) {
				$country->delete();
			}
			$country = NCountry::firstOrNew([
				'id' => $i,
			]);
			$country->code = 'C' . $i;
			$country->name = 'Country ' . $i;
			$country->save();

			//STATES
			for ($j = 1; $j <= 9; $j++) {
				$state = NState::firstOrNew([
					'country_id' => $country->id,
					'code' => 'S' . $j,
				]);
				$state->name = 'Country ' . $i . ' / State ' . $j;
				$state->created_by = $admin->id;
				$state->save();

				//CITIES
				for ($k = 1; $k <= 15; $k++) {
					$city = NCity::firstOrNew([
						'state_id' => $state->id,
						'name' => 'Country ' . $i . ' / State ' . $j . ' / City ' . $k,
					]);
					$city->created_by = $admin->id;
					$city->save();
				}
			}
		}
	}
}
