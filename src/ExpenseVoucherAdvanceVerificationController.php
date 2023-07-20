<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequestClaim;
use Yajra\Datatables\Datatables;
use Validator;

class ExpenseVoucherAdvanceVerificationController extends Controller {

	public function listExpenseVoucherverificationRequest(Request $r) {
		$expense_voucher_requests = ExpenseVoucherAdvanceRequest::select(
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.employee_id',
			'users.name as ename',
			'employees.code as ecode',
			DB::raw('DATE_FORMAT(expense_voucher_advance_requests.date,"%d-%m-%Y") as date'),
			'expense_voucher_advance_requests.advance_amount as advance_amount',
			DB::raw('IF(expense_voucher_advance_request_claims.balance_amount IS NULL,"--",expense_voucher_advance_request_claims.balance_amount) as balance_amount'),
			'expense_voucher_advance_requests.status_id as status_id',
			// 'configs.name as status'
			DB::raw('IF(advance_pcv_claim_statuses.name IS NULL,configs.name ,advance_pcv_claim_statuses.name) as status'),
			'expense_voucher_advance_requests.number as advance_pcv_number',
			'expense_voucher_advance_request_claims.number as advance_pcv_claim_number'
		)
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->join('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->join('users', 'users.entity_id', 'employees.id')
			->leftjoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
			->leftJoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
			->where('users.user_type_id', 3121)
			->where('employees.reporting_to_id', Auth::user()->entity_id)
			// ->whereIn('expense_voucher_advance_requests.status_id', [3460, 3466])
			->where(function ($query) use ($r) {
				$query->where('expense_voucher_advance_requests.status_id', 3460)
					->orWhere('expense_voucher_advance_request_claims.status_id', 3466);
			})
			->where('employees.company_id', Auth::user()->company_id)
			->where(function ($query) use ($r) {
				if (!empty($r->employee_id)) {
					$query->where('employees.id', $r->employee_id);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->date)) {
					$query->where('expense_voucher_advance_requests.date', date("Y-m-d", strtotime($r->date)));
				}
			})
			->orderBy('expense_voucher_advance_requests.id', 'desc')
		;
		// dd($expense_voucher_requests->get());
		return Datatables::of($expense_voucher_requests)
			->addColumn('action', function ($expense_voucher_requests) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				return '
				<a href="#!/expense/voucher-advance/verification1/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
			})
			->addColumn('request_type', function ($expense_voucher_requests) {
				$request_type = "Adv PCV";
				if($expense_voucher_requests->advance_pcv_claim_number){
					$request_type = "Adv PCV Claim";
				}
				return $request_type;
			})
			->make(true);
	}

	public function expenseVoucherVerificationView($id) {
		// $this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::select(
		// 	'employees.code',
		// 	'users.name',
		// 	'expense_voucher_advance_requests.employee_id',
		// 	'expense_voucher_advance_requests.date',
		// 	'expense_voucher_advance_requests.id',
		// 	'expense_voucher_advance_requests.number as advance_pcv_number',
		// 	'expense_voucher_advance_requests.advance_amount',
		// 	// 'expense_voucher_advance_requests.expense_amount',
		// 	'expense_voucher_advance_request_claims.expense_amount',
		// 	// 'expense_voucher_advance_requests.balance_amount',
		// 	'expense_voucher_advance_request_claims.balance_amount',
		// 	'expense_voucher_advance_request_claims.number as advance_pcv_claim_number',
		// 	'expense_voucher_advance_requests.description',
		// 	'expense_voucher_advance_requests.expense_description',
		// 	'configs.name as status',
		// 	'expense_voucher_advance_request_claims.status_id as advance_pcv_claim_status_id',
		// 	'advance_pcv_claim_statuses.name as advance_pcv_claim_status'
		// )
		// 	->leftJoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
		// 	->leftJoin('users', 'users.entity_id', 'employees.id')
		// 	->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
		// 	->leftjoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
		// 	->leftJoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
		// 	->where('users.user_type_id', 3121)
		// 	->where('expense_voucher_advance_requests.id', $id)
		// 	->first();
		$this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::getExpenseVoucherAdvanceRequestData($id);
		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();
		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_view->id)->select('name', 'id','view_status')->get();
		$expense_voucher_view->attachments = $expense_voucher_advance_attachment;
		$this->data['proof_view_pending_count'] = Attachment::where('attachment_of_id', 3442)
			->where('view_status', 0)
			->where('entity_id', $expense_voucher_view->id)
			->count();
		return response()->json($this->data);
	}

	//OLD 18TH JULY 2023
	// public function expenseVoucherVerificationSave(Request $request) {
	// 	// dd($request->all());
	// 	try {
	// 		DB::beginTransaction();
	// 		if ($request->approve) {
	// 			$employee_cash_check = Employee::select(
	// 				'outlets.amount_eligible',
	// 				'outlets.amount_limit'
	// 			)
	// 				->join('outlets', 'outlets.id', 'employees.outlet_id')
	// 				->where('employees.id', $request->employee_id)->first();
	// 			if ($request->expense_amount) {
	// 				if ($employee_cash_check->amount_eligible != 0) {
	// 					if ($employee_cash_check->amount_limit >= $request->advance_amount) {
	// 						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3467, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 					} else {
	// 						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3468, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 					}
	// 				} else {
	// 					$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3468, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 				}
	// 				$type = 3585;//Advance Expenses
	// 				$approval_type_id = 3617;//Advance Expenses Claim - Manager Approved
	// 				$approval_log = ApprovalLog::saveApprovalLog($type, $request->approve, $approval_type_id, Auth::user()->entity_id, Carbon::now());
	// 			} else {
	// 				if ($employee_cash_check->amount_eligible != 0) {
	// 					if ($employee_cash_check->amount_limit >= $request->advance_amount) {
	// 						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3461, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 					} else {
	// 						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3462, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 					}
	// 				} else {
	// 					$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3462, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 				}
	// 				$type = 3585;//Advance Expenses
	// 				$approval_type_id = 3614;//Advance Expenses Request - Manager Approved
	// 				$approval_log = ApprovalLog::saveApprovalLog($type, $request->approve, $approval_type_id, Auth::user()->entity_id, Carbon::now());
	// 			}

	// 			DB::commit();
	// 			return response()->json(['success' => true]);
	// 		} else {
	// 			if ($request->expense_amount) {
	// 				$expense_voucher_manager_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3469, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 			} else {
	// 				$expense_voucher_manager_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3463, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);

	// 			}
				
	// 			DB::commit();
	// 			return response()->json(['success' => true]);
	// 		}
	// 		$request->session()->flash('success', 'Expense Voucher Advance Manager Verification successfully!');
	// 		return response()->json(['success' => true]);
	// 	} catch (Exception $e) {
	// 		DB::rollBack();
	// 		return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
	// 	}
	// }

	public function expenseVoucherVerificationSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$error_messages = [
					'approve.required' => 'Expense voucher advance request ID is required',
					'approve.exists' => 'Expense voucher advance request data not found',
				];
				$validations = [
					'approve' => [
						'required',
						'exists:expense_voucher_advance_requests,id',
					],
				];
				$validator = Validator::make($request->all(), $validations, $error_messages);
				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}

				$employee_cash_check = Employee::select(
					'outlets.amount_eligible',
					'outlets.amount_limit'
				)
					->join('outlets', 'outlets.id', 'employees.outlet_id')
					->where('employees.id', $request->employee_id)->first();
				if ($request->expense_amount) {
					$expense_voucher_advance_request_claim = ExpenseVoucherAdvanceRequestClaim::where('expense_voucher_advance_request_id', $request->approve)->first();
					if(!$expense_voucher_advance_request_claim){
						return response()->json([
		                    'success' => false,
		                    'error' => 'Validation Error',
		                    'errors' => ['Expense voucher advance claim data not Found'],
		                ]);
					}

					if ($employee_cash_check->amount_eligible != 0) {
						if ($employee_cash_check->amount_limit >= $request->advance_amount) {
							// $expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3467, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
							
							$expense_voucher_advance_request_claim->status_id = 3467; //Waiting for Cashier Approval
							$expense_voucher_advance_request_claim->remarks = null;
							$expense_voucher_advance_request_claim->rejection_id = null;
							$expense_voucher_advance_request_claim->updated_by_id = Auth::user()->id;
							$expense_voucher_advance_request_claim->save();
						} else {
							// $expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3468, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);

							
							$expense_voucher_advance_request_claim->status_id = 3468; //Waiting for Financier Approval
							$expense_voucher_advance_request_claim->remarks = null;
							$expense_voucher_advance_request_claim->rejection_id = null;
							$expense_voucher_advance_request_claim->updated_by_id = Auth::user()->id;
							$expense_voucher_advance_request_claim->save();
						}
					} else {
						// $expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3468, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
						$expense_voucher_advance_request_claim->status_id = 3468; //Waiting for Financier Approval
						$expense_voucher_advance_request_claim->rejection_id = null;
						$expense_voucher_advance_request_claim->remarks = null;
						$expense_voucher_advance_request_claim->updated_by_id = Auth::user()->id;
						$expense_voucher_advance_request_claim->save();	
					}
					$type = 3585;//Advance Expenses
					$approval_type_id = 3617;//Advance Expenses Claim - Manager Approved
					$approval_log = ApprovalLog::saveApprovalLog($type, $request->approve, $approval_type_id, Auth::user()->entity_id, Carbon::now());
				} else {
					if ($employee_cash_check->amount_eligible != 0) {
						if ($employee_cash_check->amount_limit >= $request->advance_amount) {
							$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3461, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
						} else {
							$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3462, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
						}
					} else {
						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3462, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					}
					$type = 3585;//Advance Expenses
					$approval_type_id = 3614;//Advance Expenses Request - Manager Approved
					$approval_log = ApprovalLog::saveApprovalLog($type, $request->approve, $approval_type_id, Auth::user()->entity_id, Carbon::now());
				}

				DB::commit();
				return response()->json(['success' => true]);
			} else {
				$error_messages = [
					'reject.required' => 'Expense voucher advance request ID is required',
					'reject.exists' => 'Expense voucher advance request data not found',
				];
				$validations = [
					'reject' => [
						'required',
						'exists:expense_voucher_advance_requests,id',
					],
				];
				$validator = Validator::make($request->all(), $validations, $error_messages);
				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}

				if ($request->expense_amount) {
					// $expense_voucher_manager_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3469, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					$expense_voucher_advance_request_claim = ExpenseVoucherAdvanceRequestClaim::where('expense_voucher_advance_request_id', $request->reject)->first();
					if(!$expense_voucher_advance_request_claim){
						return response()->json([
		                    'success' => false,
		                    'error' => 'Validation Error',
		                    'errors' => ['Expense voucher advance claim data not Found'],
		                ]);
					}
					$expense_voucher_advance_request_claim->status_id = 3469;//Expense Manager Rejected
					$expense_voucher_advance_request_claim->remarks = $request->remarks;
					$expense_voucher_advance_request_claim->rejection_id = $request->rejection_id;
					$expense_voucher_advance_request_claim->updated_by_id = Auth::user()->id;
					$expense_voucher_advance_request_claim->save();
				} else {
					$expense_voucher_manager_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3463, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);

				}
				
				DB::commit();
				return response()->json(['success' => true]);
			}
			$request->session()->flash('success', 'Expense Voucher Advance Manager Verification successfully!');
			return response()->json(['success' => true]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile()]
			]);
		}
	}

	public function proofUploadViewStatusUpdate(Request $request) {
		try {
			$error_messages = [
				'expense_voucher_advance_request_id.required' => 'Expense Voucher Advance Request ID is required',
				'expense_voucher_advance_request_id.integer' => 'Expense Voucher Advance Request ID is invalid',
				'expense_voucher_advance_request_id.exists' => 'Expense Voucher Advance Request data is not found',
				'attachment_id.required' => 'Attachment ID is required',
				'attachment_id.integer' => 'Attachment ID is invalid',
				'attachment_id.exists' => 'Attachment data is not found',
			];
			$validations = [
				'expense_voucher_advance_request_id' => 'required|integer|exists:expense_voucher_advance_requests,id',
				'attachment_id' => 'required|integer|exists:attachments,id',
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
			$attachment = Attachment::where('id', $request->attachment_id)->first();
			$attachment->view_status = 1;
			$attachment->save();

			$proof_view_pending_count = Attachment::where('attachment_of_id', 3442)
				->where('view_status', 0)
				->where('entity_id', $request->expense_voucher_advance_request_id)
				->count();

			DB::commit();
			return response()->json([
				'success' => true,
				'attachment' => $attachment,
				'proof_view_pending_count' => $proof_view_pending_count,
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
