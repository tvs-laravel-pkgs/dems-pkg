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
		return NCountry::select('id', 'name')->get();
	}
}
