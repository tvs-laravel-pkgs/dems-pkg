<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseVoucherAdvanceRequestClaim extends Model {
	use SoftDeletes;
	protected $table = 'expense_voucher_advance_request_claims';
	protected $fillable = [
		'expense_voucher_advance_request_id'
	];

	public function employeeReturnPaymentMode() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'employee_return_payment_mode_id');
	}

	public function employeeReturnPaymentBank() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'employee_return_payment_bank_id');
	}
}
