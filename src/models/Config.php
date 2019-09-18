<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class Config extends Model {
	protected $fillable = [
		'id',
		'config_type_id',
		'name',
	];
	public $timestamps = false;

	public function configType() {
		return $this->belongsTo('App\ConfigType', 'config_type_id');
	}

	public static function expenseList() {
		return Config::where('config_type_id', 500)->select('id', 'name')->get()->keyBy('id');
	}
	public static function categoryList() {
		return Config::where('config_type_id', 525)->select('id', 'name')->get()->keyBy('id');
	}
	public static function paymentModeList() {
		return Config::where('config_type_id', 514)->select('id', 'name')->get();
	}

	public static function getBookingTypeTypeList() {
		return Config::where('config_type_id', 502)->select('id', 'name')->get();
	}

	public static function agentPaymentModeList() {
		return Config::where('config_type_id', 522)->select('id', 'name')->get();
	}

	public static function getLodgeStayTypeList() {
		return Config::where('config_type_id', 521)->select('id', 'name')->get();
	}

	public static function managerType() {
		return Config::where('config_type_id', 529)->select('id', 'name')->get();
	}
	public static function pettycashStatus() {
		return Config::where('config_type_id', 518)->select('id', 'name')->get();
	}
	public static function ExpenseVoucherAdvanceStatus() {
		return Config::where('config_type_id', 528)->select('id', 'name')->get()->toArray();
	}
	public static function ExpenseVoucherAdvanceStatusList() {
		return Config::whereIn('id', [3281, 3282])->select('id', 'name')->get()->toArray();
	}
}
