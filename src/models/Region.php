<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model {
	use SoftDeletes;
	protected $table = 'regions';

	protected $fillable = [
		// 'id',
		'company_id',
		'name',
		'code',
		'state_id',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function state() {
		return $this->belongsTo('Uitoux\EYatra\NState', 'state_id');
	}

	public static function getList() {
		$data = [];
		$option = new Region;
		$option->name = 'Select Region';
		$option->id = -1;
		$regon_list = Region::select('id', 'name')->get();
		$data = $regon_list->prepend($option);
		return $data;
	}

}
