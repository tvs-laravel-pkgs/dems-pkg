<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model {
	use SoftDeletes;
	protected $table = 'companies';
	protected $fillable = [
		'code',
		'name',
		'address',
		'cin_number',
		'gst_number',
		'customer_care_email',
		'customer_care_phone',
		'reference_code',
		'additional_approve',
		'financier_approve',

	];

	//EYATRA RELATIONSHIPS
	public function employees() {
		return $this->hasMany('Uitoux\EYatra\Employee');
	}

	public function agents() {
		return $this->hasMany('Uitoux\EYatra\Agent');
	}

	public function employeeGrades() {
		return $this->hasMany('Uitoux\EYatra\Entity')->where('entity_type_id', 500);
	}

	public function tripPurposes() {
		return $this->hasMany('Uitoux\EYatra\Entity')->where('entity_type_id', 501);
	}

	public function cityCategories() {
		return $this->hasMany('Uitoux\EYatra\Entity')->where('entity_type_id', 506);
	}

	public function travelModes() {
		return $this->hasMany('Uitoux\EYatra\Entity')->where('entity_type_id', 502);
	}

	public function localTravelModes() {
		return $this->hasMany('Uitoux\EYatra\Entity')->where('entity_type_id', 503);
	}

	// public function expenseTypes() {
	// 	return $this->hasMany('Uitoux\EYatra\Config')->where('config_type_id', 500);
	// }

	// public function employeePaymentModes() {
	// 	return $this->hasMany('Uitoux\EYatra\Config')->where('config_type_id', 514);
	// }

	public function trips() {
		return $this->hasMany('Uitoux\EYatra\Trip');
	}

	public function users() {
		return $this->hasMany('App\User');
	}

	public function createdBy() {
		return $this->belongsTo('App\User', 'created_by');
	}
	public function outlets() {
		return $this->hasMany('Uitoux\EYatra\Outlet');
	}

	public function lobs() {
		return $this->hasMany('Uitoux\EYatra\Lob');
	}

	public function designations() {
		return $this->hasMany('Uitoux\EYatra\Designation');
	}
	public function companyBudgets() {
		return $this->belongsToMany('Uitoux\EYatra\Config', 'company_budget', 'company_id', 'financial_year_id')->withPivot('outstation_budget_amount', 'local_budget_amount');
	}

	//ENDS EYATRA RELATIONSHIPS

}
