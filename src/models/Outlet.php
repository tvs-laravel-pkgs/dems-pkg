<?php

namespace Uitoux\EYatra;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model {
	use SoftDeletes;
	protected $table = 'outlets';
	protected $fillable = [
		'company_id',
		'code',
		'name',
		'sbu_id',
		'business_id',
		'cashier_id',
		'nodel_id',
		'amount_eligible',
		'expense_voucher_limit',
		'amount_limit',
		'created_by',

	];
	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function sbu() {
		return $this->belongsTo('Uitoux\EYatra\Sbu');
	}
	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'cashier_id');
	}
	public function employeeNodel() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'nodel_id');
	}

	public function businessData() {
		return $this->belongsTo('Uitoux\EYatra\Business', 'business_id');
	}

	public static function getList() {
		return Outlet::select('id', 'name')->where('company_id', Auth::user()->company_id)->get();
	}
	public static function getOutletList() {
		return Outlet::select('id', DB::raw('CONCAT(outlets.name," / ",outlets.code)as name'))->where('company_id', Auth::user()->company_id)->get();
	}

	public function address() {
		return $this->hasOne('Uitoux\EYatra\Address', 'entity_id')->where('address_of_id', 3160);
	}
	public function outletBudgets() {
		return $this->belongsToMany('Uitoux\EYatra\Sbu', 'outlet_budget', 'outlet_id', 'sbu_id')->withPivot('outstation_budget_amount','local_budget_amount');
	}
}
