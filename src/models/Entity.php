<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model {
	use SoftDeletes;
	protected $table = 'entities';

	public function expenseTypes() {
		return $this->belongsToMany('Uitoux\EYatra\Config', 'grade_expense_type', 'grade_id', 'expense_type_id');
	}

	public function tripPurposes() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'grade_trip_purpose', 'grade_id', 'trip_purpose_id');
	}

	public function localTravelModes() {
		return $this->belongsToMany('Uitoux\EYatra\Entity', 'grade_local_travel_mode', 'grade_id', 'local_travel_mode_id');
	}

	public static function purposeList() {
		return Entity::where('entity_type_id', 501)->select('id', 'name')->get();
	}

	public static function travelModeList() {
		return Entity::where('entity_type_id', 502)->select('id', 'name')->get();
	}

	public static function getGradeList() {
		return Entity::where('entity_type_id', 502)->select('id', 'name')->get();
	}

}
