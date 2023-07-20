<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
use Validator;
use Yajra\Datatables\Datatables;

class ExpenseVoucherAdvanceVerification3Controller extends Controller {
	public function listExpenseVoucherverification3Request(Request $r) {
		$expense_voucher_requests = ExpenseVoucherAdvanceRequest::select(
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.employee_id',
			'users.name as ename',
			'employees.code as ecode',
			'outlets.code as ocode',
			'outlets.name as oname',
			DB::raw('DATE_FORMAT(expense_voucher_advance_requests.date,"%d-%m-%Y") as date'),
			'expense_voucher_advance_requests.advance_amount as advance_amount',
			DB::raw('IF(expense_voucher_advance_request_claims.balance_amount IS NULL,"--",expense_voucher_advance_request_claims.balance_amount) as balance_amount'),
			'expense_voucher_advance_requests.status_id as status_id',
			// 'configs.name as status'
			DB::raw('IF(advance_pcv_claim_statuses.name IS NULL,configs.name,advance_pcv_claim_statuses.name) as status'),
			'expense_voucher_advance_requests.number as advance_pcv_number',
			'expense_voucher_advance_request_claims.number as advance_pcv_claim_number'
		)
			->join('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->join('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->join('users', 'users.entity_id', 'employees.id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->join('employees as cashier', 'cashier.id', 'outlets.cashier_id')
			->leftjoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
			->leftJoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
			// ->whereIn('expense_voucher_advance_requests.status_id', [3462, 3468])
			->where(function ($query) use ($r) {
				$query->whereIn('expense_voucher_advance_requests.status_id', [3462, 3468])
					->orWhereIn('expense_voucher_advance_request_claims.status_id', [3462, 3468]);
			})
			->where('users.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)
			->orderBy('expense_voucher_advance_requests.id', 'desc')
			->where(function ($query) use ($r) {
				if (!empty($r->employee_id)) {
					$query->where('employees.id', $r->employee_id);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->outlet)) {
					$query->where('outlets.id', $r->outlet);
				}
			})
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
				<a href="#!/expense/voucher-advance/verification3/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
			})
			->addColumn('request_type', function ($expense_voucher_requests) {
				$request_type = "Advance PCV";
				if($expense_voucher_requests->advance_pcv_claim_number){
					$request_type = "Advance PCV Claim";
				}
				return $request_type;
			})
			->make(true);
	}

	public function expenseVoucherVerification3View($id) {
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
		// 	'expense_voucher_advance_requests.description',
		// 	'expense_voucher_advance_requests.expense_description',
		// 	'configs.name as status',
		// 	'employees.payment_mode_id',
		// 	'expense_voucher_advance_requests.status_id',
		// 	'expense_voucher_advance_request_claims.status_id as advance_pcv_claim_status_id',
		// 	'expense_voucher_advance_request_claims.number as advance_pcv_claim_number',
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
		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_view->id)->select('name', 'id')->get();
		$expense_voucher_view->attachments = $expense_voucher_advance_attachment;

		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();
		return response()->json($this->data);
	}

	//OLD 18TH JULY 2023
	// public function expenseVoucherVerification3Save(Request $request) {
	// 	// dd($request->all());
	// 	try {
	// 		DB::beginTransaction();
	// 		if ($request->approve) {

	// 			$advance_petty_cash = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->first();
	// 			if ($request->expense_amount) {
	// 				if ($advance_petty_cash->advance_amount > $advance_petty_cash->expense_amount) {
	// 					$advance_petty_cash->status_id = 3472;
	// 				} elseif ($advance_petty_cash->advance_amount < $advance_petty_cash->expense_amount) {
	// 					$advance_petty_cash->status_id = 3470;
	// 				} else {
	// 					$advance_petty_cash->status_id = 3470;
	// 				}
	// 			} else {
	// 				$advance_petty_cash->status_id = 3464;
	// 			}
	// 			$advance_petty_cash->remarks = NULL;
	// 			$advance_petty_cash->rejection_id = NULL;
	// 			$advance_petty_cash->updated_at = Carbon::now();
	// 			$advance_petty_cash->updated_by = Auth::user()->id;
	// 			$advance_petty_cash->save();

	// 			if (isset($request->type_id) && $request->type_id > 0) {
	// 				if ($advance_petty_cash) {
	// 					$error_messages = [
	// 						'reference_number.unique' => "Reference Number is already taken",
	// 					];

	// 					$validator = Validator::make($request->all(), [
	// 						'reference_number' => [
	// 							'required:true',
	// 							'unique:payments,reference_number',

	// 						],
	// 					], $error_messages);

	// 					if ($validator->fails()) {
	// 						return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
	// 					}

	// 					$employee = Employee::where('id', $request->employee_id)->first();
	// 					//Check Outlet have reimpursement amount or not
	// 					$outlet_reimbursement_amount = Outlet::where('id', $employee->outlet_id)->pluck('reimbursement_amount')->first();
	// 					if ($outlet_reimbursement_amount > 0) {
	// 						//Reimbursement Transaction
	// 						$previous_balance_amount = ReimbursementTranscation::where('outlet_id', $employee->outlet_id)->where('company_id', Auth::user()->company_id)->orderBy('id', 'desc')->pluck('balance_amount')->first();
	// 						// dd($previous_balance_amount);
	// 						if ($previous_balance_amount) {
	// 							$balance_amount = $previous_balance_amount - $request->amount;
	// 							$reimbursementtranscation = new ReimbursementTranscation;
	// 							$reimbursementtranscation->outlet_id = $employee->outlet_id;
	// 							$reimbursementtranscation->company_id = Auth::user()->company_id;
	// 							if ($request->type_id == 1) {
	// 								$reimbursementtranscation->transcation_id = 3273;
	// 								$reimbursementtranscation->transcation_type = 3273;
	// 							} else {
	// 								$reimbursementtranscation->transcation_id = 3274;
	// 								$reimbursementtranscation->transcation_type = 3274;
	// 							}
	// 							$reimbursementtranscation->transaction_date = Carbon::now();

	// 							$reimbursementtranscation->petty_cash_id = $request->id;
	// 							$reimbursementtranscation->amount = $request->amount;
	// 							$reimbursementtranscation->balance_amount = $balance_amount;
	// 							$reimbursementtranscation->save();
	// 							//Outlet
	// 							$outlet = Outlet::where('id', $employee->outlet_id)->update(['reimbursement_amount' => $balance_amount]);
	// 						} else {
	// 							//dd(Auth::user()->entity->outlet_id);
	// 							$outlet = Outlet::where('id', $employee->outlet_id)->where('company_id', Auth::user()->company_id)->select('reimbursement_amount')->first();
	// 							//dd($outlet->reimbursement_amount);
	// 							if ($outlet->reimbursement_amount >= 0) {
	// 								$balance_amount = $outlet->reimbursement_amount - $request->amount;
	// 								$reimbursementtranscation = new ReimbursementTranscation;
	// 								$reimbursementtranscation->outlet_id = $employee->outlet_id;
	// 								$reimbursementtranscation->company_id = Auth::user()->company_id;
	// 								if ($request->type_id == 1) {
	// 									$reimbursementtranscation->transcation_id = 3273;
	// 									$reimbursementtranscation->transcation_type = 3273;
	// 								} else {
	// 									$reimbursementtranscation->transcation_id = 3274;
	// 									$reimbursementtranscation->transcation_type = 3274;
	// 								}
	// 								$reimbursementtranscation->transaction_date = Carbon::now();
	// 								$reimbursementtranscation->petty_cash_id = $request->id;
	// 								$reimbursementtranscation->amount = $request->amount;
	// 								$reimbursementtranscation->balance_amount = $balance_amount;
	// 								$reimbursementtranscation->save();
	// 								$outlet = Outlet::where('id', $employee->outlet_id)->update(['reimbursement_amount' => $balance_amount, 'updated_at' => Carbon::now()]);

	// 							} else {
	// 								return response()->json(['success' => false, 'errors' => ['This outlet has no expense voucher amount']]);
	// 							}
	// 						}
	// 						//PAYMENT SAVE
	// 						if ($request->type_id == 1) {
	// 							//Advance Approval
	// 							$payment = Payment::firstOrNew(['entity_id' => $request->id, 'payment_of_id' => 3256, 'payment_mode_id' => $request->payment_mode_id]);
	// 							$payment->fill($request->all());
	// 							$payment->date = date('Y-m-d', strtotime($request->date));
	// 							$payment->payment_of_id = 3256; //Employee Petty Cash Advance Expense Request
	// 							$payment->payment_mode_id = 3244; //BANK
	// 							$payment->created_by = Auth::user()->id;
	// 							$payment->save();
	// 							$activity['entity_id'] = $request->id;
	// 							$activity['entity_type'] = 'Advance Expense';
	// 							$activity['details'] = "Advance Expense Approved";
	// 							$activity['activity'] = "claim";
	// 							$activity_log = ActivityLog::saveLog($activity);
	// 						} elseif ($request->type_id == 2) {
	// 							//Expense Approval
	// 							$payment = Payment::firstOrNew(['entity_id' => $request->id, 'payment_of_id' => 3257, 'payment_mode_id' => $request->payment_mode_id]);
	// 							$payment->fill($request->all());
	// 							$payment->date = date('Y-m-d', strtotime($request->date));
	// 							$payment->payment_of_id = 3257; //Employee Petty Cash Advance Expense Claim
	// 							$payment->payment_mode_id = 3244; //BANK
	// 							$payment->created_by = Auth::user()->id;
	// 							$payment->save();
	// 							$activity['entity_id'] = $request->id;
	// 							$activity['entity_type'] = 'Advance Expense';
	// 							$activity['details'] = "Advance Expense Paid";
	// 							$activity['activity'] = "paid";
	// 							$activity_log = ActivityLog::saveLog($activity);
	// 						}
	// 						//Approval Log
	// 						if ($request->type_id == 1) {
	// 							$type = 3585; //Advance Expenses
	// 							$approval_type_id = 3616; //Advance Expenses Request - Financier Approved
	// 						} else {
	// 							$type = 3585;
	// 							$approval_type_id = 3619; //Advance Expenses Claim - Financier Approved
	// 						}
	// 						$approval_log = ApprovalLog::saveApprovalLog($type, $request->id, $approval_type_id, Auth::user()->entity_id, Carbon::now());
	// 						DB::commit();
	// 						return response()->json(['success' => true]);
	// 					} else {
	// 						return response()->json(['success' => false, 'errors' => ['This outlet has no reimbursement amount']]);
	// 					}

	// 				}
	// 			}

	// 		} else {
	// 			if ($request->expense_amount) {
	// 				$expence_voucher_finance_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3471, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 			} else {
	// 				$expence_voucher_finance_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3465, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
	// 			}
	// 		}
	// 		DB::commit();
	// 		return response()->json(['success' => true]);
	// 		$request->session()->flash('success', 'Expense Voucher Advance Manager Verification successfully!');
	// 		return response()->json(['success' => true]);
	// 	} catch (Exception $e) {
	// 		DB::rollBack();
	// 		return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
	// 	}
	// }

	public function expenseVoucherVerification3Save(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->approve) {

				$advance_petty_cash = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->first();
				$advance_pcv_claim = ExpenseVoucherAdvanceRequestClaim::where('expense_voucher_advance_request_id', $request->approve)->first();
				if ($request->expense_amount) {
					if ($advance_petty_cash->advance_amount > $advance_pcv_claim->expense_amount) {
						// $advance_petty_cash->status_id = 3472;
						$advance_pcv_claim->status_id = 3472; //Payment Pending from Employee
					} elseif ($advance_petty_cash->advance_amount < $advance_pcv_claim->expense_amount) {
						$advance_petty_cash->status_id = 3470; //Paid
						$advance_pcv_claim->status_id = 3470; //Paid
					} else {
						$advance_petty_cash->status_id = 3470; // Paid
						$advance_pcv_claim->status_id = 3470; // Paid
					}

					$advance_pcv_claim->remarks = null;
					$advance_pcv_claim->rejection_id = null;
					$advance_pcv_claim->updated_by_id = Auth::user()->id;
					$advance_pcv_claim->save();
				} else {
					$advance_petty_cash->status_id = 3464; //Advance Amount Approved
				}
				$advance_petty_cash->remarks = NULL;
				$advance_petty_cash->rejection_id = NULL;
				$advance_petty_cash->updated_at = Carbon::now();
				$advance_petty_cash->updated_by = Auth::user()->id;
				$advance_petty_cash->save();

				if (isset($request->type_id) && $request->type_id > 0) {
					if ($advance_petty_cash) {
						$error_messages = [
							'reference_number.unique' => "Reference Number is already taken",
						];

						$validator = Validator::make($request->all(), [
							'reference_number' => [
								'required:true',
								'unique:payments,reference_number',

							],
						], $error_messages);

						if ($validator->fails()) {
							return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
						}

						$employee = Employee::where('id', $request->employee_id)->first();
						//Check Outlet have reimpursement amount or not
						$outlet_reimbursement_amount = Outlet::where('id', $employee->outlet_id)->pluck('reimbursement_amount')->first();
						if ($outlet_reimbursement_amount > 0) {
							//Reimbursement Transaction
							$previous_balance_amount = ReimbursementTranscation::where('outlet_id', $employee->outlet_id)->where('company_id', Auth::user()->company_id)->orderBy('id', 'desc')->pluck('balance_amount')->first();
							// dd($previous_balance_amount);
							if ($previous_balance_amount) {
								$balance_amount = $previous_balance_amount - $request->amount;
								$reimbursementtranscation = new ReimbursementTranscation;
								$reimbursementtranscation->outlet_id = $employee->outlet_id;
								$reimbursementtranscation->company_id = Auth::user()->company_id;
								if ($request->type_id == 1) {
									$reimbursementtranscation->transcation_id = 3273;
									$reimbursementtranscation->transcation_type = 3273;
								} else {
									$reimbursementtranscation->transcation_id = 3274;
									$reimbursementtranscation->transcation_type = 3274;
								}
								$reimbursementtranscation->transaction_date = Carbon::now();

								$reimbursementtranscation->petty_cash_id = $request->id;
								$reimbursementtranscation->amount = $request->amount;
								$reimbursementtranscation->balance_amount = $balance_amount;
								$reimbursementtranscation->save();
								//Outlet
								$outlet = Outlet::where('id', $employee->outlet_id)->update(['reimbursement_amount' => $balance_amount]);
							} else {
								//dd(Auth::user()->entity->outlet_id);
								$outlet = Outlet::where('id', $employee->outlet_id)->where('company_id', Auth::user()->company_id)->select('reimbursement_amount')->first();
								//dd($outlet->reimbursement_amount);
								if ($outlet->reimbursement_amount >= 0) {
									$balance_amount = $outlet->reimbursement_amount - $request->amount;
									$reimbursementtranscation = new ReimbursementTranscation;
									$reimbursementtranscation->outlet_id = $employee->outlet_id;
									$reimbursementtranscation->company_id = Auth::user()->company_id;
									if ($request->type_id == 1) {
										$reimbursementtranscation->transcation_id = 3273;
										$reimbursementtranscation->transcation_type = 3273;
									} else {
										$reimbursementtranscation->transcation_id = 3274;
										$reimbursementtranscation->transcation_type = 3274;
									}
									$reimbursementtranscation->transaction_date = Carbon::now();
									$reimbursementtranscation->petty_cash_id = $request->id;
									$reimbursementtranscation->amount = $request->amount;
									$reimbursementtranscation->balance_amount = $balance_amount;
									$reimbursementtranscation->save();
									$outlet = Outlet::where('id', $employee->outlet_id)->update(['reimbursement_amount' => $balance_amount, 'updated_at' => Carbon::now()]);

								} else {
									return response()->json(['success' => false, 'errors' => ['This outlet has no expense voucher amount']]);
								}
							}
							//PAYMENT SAVE
							if ($request->type_id == 1) {
								//Advance Approval
								$payment = Payment::firstOrNew(['entity_id' => $request->id, 'payment_of_id' => 3256, 'payment_mode_id' => $request->payment_mode_id]);
								$payment->fill($request->all());
								$payment->date = date('Y-m-d', strtotime($request->date));
								$payment->payment_of_id = 3256; //Employee Petty Cash Advance Expense Request
								$payment->payment_mode_id = 3244; //BANK
								$payment->created_by = Auth::user()->id;
								$payment->save();
								$activity['entity_id'] = $request->id;
								$activity['entity_type'] = 'Advance Expense';
								$activity['details'] = "Advance Expense Approved";
								$activity['activity'] = "claim";
								$activity_log = ActivityLog::saveLog($activity);
							} elseif ($request->type_id == 2) {
								//Expense Approval
								$payment = Payment::firstOrNew(['entity_id' => $request->id, 'payment_of_id' => 3257, 'payment_mode_id' => $request->payment_mode_id]);
								$payment->fill($request->all());
								$payment->date = date('Y-m-d', strtotime($request->date));
								$payment->payment_of_id = 3257; //Employee Petty Cash Advance Expense Claim
								$payment->payment_mode_id = 3244; //BANK
								$payment->created_by = Auth::user()->id;
								$payment->save();
								$activity['entity_id'] = $request->id;
								$activity['entity_type'] = 'Advance Expense';
								$activity['details'] = "Advance Expense Paid";
								$activity['activity'] = "paid";
								$activity_log = ActivityLog::saveLog($activity);
							}
							//Approval Log
							if ($request->type_id == 1) {
								$type = 3585; //Advance Expenses
								$approval_type_id = 3616; //Advance Expenses Request - Financier Approved
							} else {
								$type = 3585;
								$approval_type_id = 3619; //Advance Expenses Claim - Financier Approved
							}
							$approval_log = ApprovalLog::saveApprovalLog($type, $request->id, $approval_type_id, Auth::user()->entity_id, Carbon::now());
							DB::commit();
							return response()->json(['success' => true]);
						} else {
							return response()->json(['success' => false, 'errors' => ['This outlet has no reimbursement amount']]);
						}

					}
				}

			} else {
				if ($request->expense_amount) {
					// $expence_voucher_finance_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3471, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					$expense_voucher_advance_request_claim = ExpenseVoucherAdvanceRequestClaim::where('expense_voucher_advance_request_id', $request->reject)->first();
					if($expense_voucher_advance_request_claim){
						$expense_voucher_advance_request_claim->status_id = 3471;//Expense Claim Rejected
						$expense_voucher_advance_request_claim->remarks = $request->remarks;
						$expense_voucher_advance_request_claim->rejection_id = $request->rejection_id;
						$expense_voucher_advance_request_claim->updated_by_id = Auth::user()->id;
						$expense_voucher_advance_request_claim->save();
					}
				} else {
					$expence_voucher_finance_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3465, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				}
			}
			DB::commit();
			return response()->json(['success' => true]);
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
}
