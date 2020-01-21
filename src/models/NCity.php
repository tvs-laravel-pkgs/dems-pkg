<?php

namespace Uitoux\EYatra;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NCity extends Model {
	use SoftDeletes;
	protected $table = 'ncities';
	protected $fillable = [
		'state_id',
		'name',
		'category_id',
		'company_id',
		'created_by',
		'updated_by',
		'deleted_by',
	];
	public function state() {
		return $this->belongsTo('Uitoux\EYatra\NState');
	}

	public static function getList($state_id = NULL) {
		$data = [];
		$option = new NCity;
		$option->name = 'Select City';
		$option->id = NULL;
		if (!$state_id) {
			$city_list = NCity::leftJoin('nstates', 'ncities.state_id', 'nstates.id')->select('ncities.id', DB::raw('CONCAT(ncities.name," - ",nstates.name) as name'))->where('company_id', Auth::user()->company_id)->get();
			$data = $city_list->prepend($option);
			return $data;
			// return NCity::select('id', 'name')->get();
		} else {
			$city_list = NCity::leftJoin('nstates', 'ncities.state_id', 'nstates.id')->select('ncities.id', DB::raw('CONCAT(ncities.name,"/",nstates.name) as name'))->where('company_id', Auth::user()->company_id)->where('ncities.state_id', $state_id)->get();
			$data = $city_list->prepend($option);
			return $data;
		}
	}

}
