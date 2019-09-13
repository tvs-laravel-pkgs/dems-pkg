<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
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
			->where('users.user_type_id', 3121)
			->where('cashier.id', Auth::user()->entity_id)
			->where('expense_voucher_advance_requests.status_id', 3281)
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
				<a href="#!/eyatra/expense/voucher-advance/verification2/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
			})
			->make(true);
	}

	public function expenseVoucherVerification2View($id) {
		$this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::select(
			'employees.code',
			'users.name',
			'expense_voucher_advance_requests.employee_id',
			'expense_voucher_advance_requests.date',
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.advance_amount',
			'expense_voucher_advance_requests.expense_amount',
			'expense_voucher_advance_requests.balance_amount',
			'expense_voucher_advance_requests.description',
			'configs.name as status',
			'employees.payment_mode_id'
		)
			->leftJoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->where('users.user_type_id', 3121)
			->where('expense_voucher_advance_requests.id', $id)
			->first();

		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();
		$this->data['bank_detail'] = $bank_detail = BankDetail::where('entity_id', $petty_cash[0]->employee_id)->where('detail_of_id', 3121)->first();
		$this->data['cheque_detail'] = $cheque_detail = ChequeDetail::where('entity_id', $petty_cash[0]->employee_id)->where('detail_of_id', 3121)->first();
		$this->data['wallet_detail'] = $wallet_detail = WalletDetail::where('entity_id', $petty_cash[0]->employee_id)->where('wallet_of_id', 3121)->first();
		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$this->data['payment_mode_list'] = $payment_mode_list;
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);
		$this->data['wallet_mode_list'] = $wallet_mode_list;
		return response()->json($this->data);
	}

	public function expenseVoucherVerification2Save(Request $request) {
		dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$expence_voucher_cashier_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3461, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				// //PAYMENT SAVE
				// $payment = Payment::firstOrNew(['entity_id' => $request->approve, 'payment_of_id' => 3254, 'payment_mode_id' => $request->payment_mode_id]);
				// $payment->fill($request->all());
				// $payment->date = date('Y-m-d', strtotime($request->date));
				// $payment->payment_of_id = 3254;
				// // $payment->payment_mode_id = $agent_claim->id;
				// $payment->created_by = Auth::user()->id;
				// $payment->save();
				// $activity['entity_id'] = $request->approve;
				// $activity['entity_type'] = 'Expence Voucher';
				// $activity['details'] = "Claim is paid by Cashier";
				// $activity['activity'] = "paid";
				// $activity_log = ActivityLog::saveLog($activity);
			} else {
				$expence_voucher_cashier_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3462, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);

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
