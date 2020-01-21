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
		'expense_amount',
		'status_id',
	];
	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee');
	}
	public function setDateAttribute($value) {
		return $this->attributes['date'] = $value ? date('Y-m-d', strtotime($value)) : date('Y-m-d');
	}

	public function getDateAttribute($value) {
		return date('d-m-Y', strtotime($value));
	}
}
