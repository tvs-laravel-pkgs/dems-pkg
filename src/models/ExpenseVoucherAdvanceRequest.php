<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseVoucherAdvanceRequest extends Model {
	use SoftDeletes;
	protected $table = 'expense_voucher_advance_requests';
	protected $fillable = [
		'employee_id',
		'date',
		'advance_amount',
		// 'expense_amount',
		'status_id',
	];
	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}
	
	public function setDateAttribute($value) {
		return $this->attributes['date'] = $value ? date('Y-m-d', strtotime($value)) : date('Y-m-d');
	}

	public function getDateAttribute($value) {
		return date('d-m-Y', strtotime($value));
	}

	public static function getExpenseVoucherAdvanceRequestData($id){
		 $expense_voucher_advance_request = ExpenseVoucherAdvanceRequest::select([
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.number as advance_pcv_number',
			'expense_voucher_advance_requests.date',
			'expense_voucher_advance_requests.advance_amount',
			'expense_voucher_advance_requests.description',
			'expense_voucher_advance_requests.status_id',
			'configs.name as status',
			'expense_voucher_advance_request_claims.number as advance_pcv_claim_number',
			'expense_voucher_advance_request_claims.expense_amount',
			'expense_voucher_advance_request_claims.balance_amount',
			'expense_voucher_advance_request_claims.description as expense_description',
			'expense_voucher_advance_request_claims.status_id as advance_pcv_claim_status_id',
			'advance_pcv_claim_statuses.name as advance_pcv_claim_status',
			'employees.code',
			'employees.id as employee_id',
			'employees.payment_mode_id',
			'users.name',
			'employee_return_payment_modes.name as employee_return_payment_mode',
		])
			->leftjoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftjoin('users', 'users.entity_id', 'employees.id')
			->leftjoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->leftjoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
			->leftjoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
			->leftjoin('configs as employee_return_payment_modes', 'employee_return_payment_modes.id', 'expense_voucher_advance_request_claims.employee_return_payment_mode_id')
			->where('users.user_type_id', 3121) //EMPLOYEE
			->where('expense_voucher_advance_requests.id', $id)
			->first();
		return $expense_voucher_advance_request;
	}
}
