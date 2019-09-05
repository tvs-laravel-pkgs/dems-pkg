<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class GradeAdvancedEligiblity extends Model {
	protected $table = 'grade_advanced_eligibility';

	protected $fillable = [
		'grade_id',
		'advanced_eligibility',
		'stay_type_disc',
		'deviation_eligiblity',
	];

	public function gradeinfo() {
		return $this->belongsTo('Uitoux\EYatra\GradeAdvancedEligiblity', 'grade_id');
	}

}
