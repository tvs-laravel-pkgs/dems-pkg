<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model {
	use SoftDeletes;
	protected $table = 'designations';
	protected $fillable = [
		'id',
		'company_id',
		'name',
		'grade_id',
	];

	public static function designationList() {
		return Designation::select('id', 'name')->get()->keyBy('id');
	}

	public static function create($company, $admin) {
		for ($i = 1; $i < 15; $i++) {
			$designation = Designation::firstOrNew([
				'company_id' => $company->id,
				'name' => 'c' . $company->id . '/d' . $i,
			]);
			$designation->created_by = $admin->id;
			$designation->save();
		}
	}
}
