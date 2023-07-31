<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Uitoux\EYatra\ActivityLog;
use Validator;
use Auth;

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

	public static function proofViewUpdate(Request $request){
		try {
			$error_messages = [
				'attachment_id.required' => 'Attachment ID is required',
				'attachment_id.integer' => 'Attachment ID is invalid',
				'attachment_id.exists' => 'Attachment data is not found',
				'expense_voucher_advance_request_id.required' => 'Expense Voucher Advance Request ID is required',
				'expense_voucher_advance_request_id.integer' => 'Expense Voucher Advance Request ID is invalid',
				'expense_voucher_advance_request_id.exists' => 'Expense Voucher Advance Request data is not found',
				'activity_id.required' => 'Activity ID is required',
				'activity_id.integer' => 'Activity ID is invalid',
				'activity_id.exists' => 'Activity data is not found',
				'activity.required' => 'Activity is required',
			];
			$validations = [
				'attachment_id' => 'required|integer|exists:attachments,id',
				'expense_voucher_advance_request_id' => 'required|integer|exists:expense_voucher_advance_requests,id',
				'activity_id' => 'required|integer|exists:configs,id',
				'activity' => 'required',
			];
			$validator = Validator::make($request->all(), $validations, $error_messages);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$activity['entity_id'] = $request->attachment_id;
			$activity['entity_type'] = 'Advance PCV Attachment';
			$activity['details'] = "Attachment is viewed";
			$activity['activity'] = $request->activity;
			$activity_log = ActivityLog::saveLog($activity);

			$advance_pcv_attachment_ids = Attachment::where('attachment_of_id', 3442)
				->where('attachment_type_id', 3200)
				->where('entity_id', $request->expense_voucher_advance_request_id)
				->pluck('id');
			$advance_pcv_attachment_count = Attachment::where('attachment_of_id', 3442)
				->where('attachment_type_id', 3200)
				->where('entity_id', $request->expense_voucher_advance_request_id)
				->count();
			$viewed_attachment_count = ActivityLog::where('user_id' , Auth::id())
				->whereIn('entity_id', $advance_pcv_attachment_ids)
				->where('entity_type_id', 4038)
				->where('activity_id', $request->activity_id)
				->count();

			$proof_view_pending = false;	
			if($advance_pcv_attachment_count != $viewed_attachment_count){
				$proof_view_pending = true;
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'proof_view_pending' => $proof_view_pending,
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => ['Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile()],
			]);
		}
	}
}
