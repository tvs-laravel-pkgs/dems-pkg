<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlternateApprove extends Model {
	use SoftDeletes;
	protected $table = 'alternative_approvers';

	protected $fillable = [
		'employee_id',
		'alternate_employee_id',
		'from',
		'to',
		'type',
		'created_by',
	];

	public function getFromAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
	public function getToAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'employee_id');
	}

	public function altEmployee() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'alternate_employee_id');
	}

}