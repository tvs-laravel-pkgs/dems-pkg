<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
use Yajra\Datatables\Datatables;

class ExpenseVoucherAdvanceVerificationController extends Controller {

	public function listExpenseVoucherverificationRequest(Request $r) {
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
			->where('users.user_type_id', 3121)
			->where('employees.reporting_to_id', Auth::user()->entity_id)
			->whereIn('expense_voucher_advance_requests.status_id', [3460, 3463])
			->where('employees.company_id', Auth::user()->company_id)
			->where(function ($query) use ($r) {
				if (!empty($r->employee_id)) {
					$query->where('employees.id', $r->employee_id);
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
				<a href="#!/eyatra/expense/voucher-advance/verification1/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
			})
			->make(true);
	}

	public function expenseVoucherVerificationView($id) {
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
			'expense_voucher_advance_requests.expense_description',
			'configs.name as status'
		)
			->leftJoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->where('users.user_type_id', 3121)
			->where('expense_voucher_advance_requests.id', $id)
			->first();
		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();
		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_view->id)->select('name', 'id')->get();
		$expense_voucher_view->attachments = $expense_voucher_advance_attachment;

		return response()->json($this->data);
	}

	public function expenseVoucherVerificationSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$employee_cash_check = Employee::select(
					'outlets.amount_eligible',
					'outlets.amount_limit'
				)
					->join('outlets', 'outlets.id', 'employees.outlet_id')
					->where('employees.id', $request->employee_id)->first();
				if ($employee_cash_check->amount_eligible != 0) {
					if ($employee_cash_check->amount_limit >= $request->advance_amount) {
						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3281, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					} else {
						$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3285, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					}
				} else {
					$expense_voucher_manager_approve = ExpenseVoucherAdvanceRequest::where('id', $request->approve)->update(['status_id' => 3285, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				}
				DB::commit();
				return response()->json(['success' => true]);
			} else {
				if ($request->expense_amount) {
					$expense_voucher_manager_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3464, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				} else {
					$expense_voucher_manager_reject = ExpenseVoucherAdvanceRequest::where('id', $request->reject)->update(['status_id' => 3282, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);

				}
				DB::commit();
				return response()->json(['success' => true]);
			}
			$request->session()->flash('success', 'Expense Voucher Advance Manager Verification successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
