<?php

namespace Uitoux\EYatra;

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
		$countries_list = NCountry::select('name', 'id')->get();
		$data = $countries_list->prepend($option);
		return $data;
		// return NCountry::select('id', 'name')->get();
	}

	public function states() {
		return $this->hasMany('Uitoux\EYatra\NState', 'country_id');
	}
}
