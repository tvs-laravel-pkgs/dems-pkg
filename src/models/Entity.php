<?php

namespace Uitoux\EYatra;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model {
	use SoftDeletes;
	protected $table = 'entities';
	protected $fillable = [
		'id',
		'company_id',
		'entity_type_id',
		'name',
		'created_by',
	];

	public function expenseTypes() {
		return $this->belongsToMany('Uitoux\EYatra\Config', 'grade_expense_type', 'grade_id', 'expense_type_id')->withPivot('eligible_amount', 'city_category_id');
	}

	public function tripPurposes() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'grade_trip_purpose', 'grade_id', 'trip_purpose_id');
	}

	public function localTravelModes() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'grade_local_travel_mode', 'grade_id', 'local_travel_mode_id');
	}

	public function travelModes() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'grade_travel_mode', 'grade_id', 'travel_mode_id');
	}

	public function gradeEligibility() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'grade_advanced_eligibility', 'grade_id', 'advanced_eligibility');
	}

	public static function purposeList() {
		return Entity::where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->select('id', 'name')->get()->keyBy('id');
	}

	public static function travelModeList() {
		return Entity::where('entity_type_id', 502)->where('company_id', Auth::user()->company_id)->select('id', 'name')->get()->keyBy('id');
	}

	public static function localTravelModeList() {
		return Entity::where('entity_type_id', 503)->where('company_id', Auth::user()->company_id)->select('id', 'name')->get()->keyBy('id');
	}

	public static function uiPurposeList() {
		return Entity::where('entity_type_id', 501)->select('id', 'name')->where('company_id', Auth::user()->company_id)->get();
	}
	public static function uiExpenceTypeList() {
		return Entity::where('entity_type_id', 512)->select('id', 'name')->where('company_id', Auth::user()->company_id)->get();
	}
	public static function uiExpenceTypeListBasedPettyCash() {
		return Entity::where('entity_type_id', 512)->select('id', 'name')->where('company_id', Auth::user()->company_id)->where('name', 'NOT LIKE', '%Local Conveyance%')->get();
	}

	public static function uiTravelModeList() {
		return Entity::where('entity_type_id', 502)->select('id', 'name')->where('company_id', Auth::user()->company_id)->get();
	}

	public static function getGradeList() {
		return Entity::where('entity_type_id', 500)->select('id', 'name')->get();
	}

	public static function getLodgeStayTypeList() {
		return Entity::where('entity_type_id', 504)->select('id', 'name')->get();
	}
	public static function walletModeList() {
		return Entity::where('entity_type_id', 505)->select('id', 'name')->get();
	}
	public static function cityCategoryList() {
		return Entity::where('entity_type_id', 506)->where('company_id', Auth::user()->company_id)->select('id', 'name')->get();
	}

	public static function create($sample_entities, $admin, $company) {
		foreach ($sample_entities as $entity_type_id => $entities) {
			foreach ($entities as $entity_name) {
				$record = Entity::firstOrNew([
					'entity_type_id' => $entity_type_id,
					'company_id' => $company->id,
					'name' => $entity_name,
				]);
				$record->created_by = $admin->id;
				$record->save();
			}
		}

	}
}
