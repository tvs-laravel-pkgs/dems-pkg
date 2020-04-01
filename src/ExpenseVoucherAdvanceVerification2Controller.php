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

class ExpenseVoucherAdvanceVerification2Controller extends Controller {
	public function listExpenseVoucherverification2Request(Request $r) {
		$expense_voucher_requests = ExpenseVoucherAdvanceRequest::select(
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.employee_id',
			'users.name as ename',
			'employees.code as ecode',
			DB::raw('DATE_FORMAT(expense_voucher_advance_requests.date,"%d-%m-%Y") as date'),
			'expense_voucher_advance_requests.advance_amount as advance_amount',
			DB::raw('IF(expense_voucher_advance_requests.balance_amount IS NULL,"--",expense_voucher_advance_requests.balance_amount) as balance_amount'),
			'expense_voucher_advance_requests.status_id as status_id',
			'configs.name as status'
		)
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->join('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->join('users', 'users.entity_id', 'employees.id')
			->join('employees as cashier', 'cashier.id', 'outlets.cashier_id')
			->where(function ($query) use ($r) {
				if (!empty($r->employee_id)) {
					$query->where('employees.id', $r->employee_id);
				}
			})
			->where('users.user_type_id', 3121)
			->where('cashier.id', Auth::user()->entity_id)
			->whereIn('expense_voucher_advance_requests.status_id', [3461, 3467])
			->where('employees.company_id', Auth::user()->company_id)
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
				<a href="#!/expense/voucher-advance/verification2/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
			})
			->make(true);
	}

	public function expenseVoucherVerification2View($id) {
		$this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::select(
			'employees.code',
			'employees.id as employee_id',
			'users.name',
			'expense_voucher_advance_requests.employee_id',
			'expense_voucher_advance_requests.date',
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.advance_amount',
			'expense_voucher_advance_requests.expense_amount',
			'expense_voucher_advance_requests.balance_amount',
			'expense_voucher_advance_requests.description',
			'expense_voucher_advance_requests.expense_description',
			'configs.name as status',
			'employees.payment_mode_id',
			'expense_voucher_advance_requests.status_id'
		)
			->leftJoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->where('users.user_type_id', 3121)
			->where('expense_voucher_advance_requests.id', $id)
			->first();
		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_view->id)->select('name', 'id')->get();
		$expense_voucher_view->attachments = $expense_voucher_advance_attachment;

		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();
		$this->data['bank_detail'] = $bank_detail = BankDetail::where('entity_id', $expense_voucher_view->employee_id)->where('detail_of_id', 3121)->first();
		$this->data['cheque_detail'] = $cheque_detail = ChequeDetail::where('entity_id', $expense_voucher_view->employee_id)->where('detail_of_id', 3121)->first();
		$this->data['wallet_detail'] = $wallet_detail = WalletDetail::where('entity_id', $expense_voucher_view->employee_id)->where('wallet_of_id', 3121)->first();
		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$this->data['payment_mode_list'] = $payment_mode_list;
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);
		$this->data['wallet_mode_list'] = $wallet_mode_list;
		return response()->json($this->data);
	}

	public function expenseVoucherVerification2Save(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$amount = $request->amount;
				$advance_petty_cash = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->first();
				if ($request->expense_amount) {
					if ($advance_petty_cash->advance_amount > $advance_petty_cash->expense_amount) {
						$advance_petty_cash->status_id = 3472;
					} elseif ($advance_petty_cash->advance_amount < $advance_petty_cash->expense_amount) {
						$advance_petty_cash->status_id = 3470;
					} else {
						$advance_petty_cash->status_id = 3470;
					}
				} else {
					$advance_petty_cash->status_id = 3464;
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
								$outlet = Outlet::where('id', $employee->outlet_id)->update(['reimbursement_amount' => $balance_amount, 'updated_at' => Carbon::now()]);
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
								$approval_type_id = 3615; //Advance Expenses Request - Cashier Approved
							} else {
								$type = 3585;
								$approval_type_id = 3618; //Advance Expenses Claim - Cashier Approved
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
					$expence_voucher_cashier_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3471, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				} else {
					$expence_voucher_cashier_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3465, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				}
			}
			DB::commit();
			return response()->json(['success' => true]);
			$request->session()->flash('success', 'Expense Voucher Advance Manager Verification successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
