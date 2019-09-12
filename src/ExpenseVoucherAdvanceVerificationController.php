<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
use Validator;
use Yajra\Datatables\Datatables;

class ExpenseVoucherAdvanceVerificationController extends Controller {
	public function listExpenseVoucherverificationRequest(Request $r) {
		$expense_voucher_requests = ExpenseVoucherAdvanceRequest::select(
			'expense_voucher_advance_requests.id',
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
			->join('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->orderBy('expense_voucher_advance_requests.id', 'desc')
			->groupBy('expense_voucher_advance_requests.id')
		;

		return Datatables::of($expense_voucher_requests)
			->addColumn('action', function ($expense_voucher_requests) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				return '
				<a href="#!/eyatra/expense/voucher-advance/verification/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function expenseVoucherVerificationView($id) {
		$this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::select(
			'employees.code',
			'users.name',
			'expense_voucher_advance_requests.date',
			'expense_voucher_advance_requests.advance_amount',
			'expense_voucher_advance_requests.expense_amount',
			'expense_voucher_advance_requests.balance_amount',
			'expense_voucher_advance_requests.description',
			'configs.name as status'
		)
			->leftJoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->leftJoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->where('users.user_type_id', 3121)
			->where('expense_voucher_advance_requests.id', $id)
			->first();
		// dd($expense_voucher_view);
		return response()->json($this->data);

	}

	public function expenseVoucherVerificationSave(Request $request) {
		dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'employee_id' => [
					'required',
				],
				'date' => [
					'required',
				],
				'advance_amount' => [
					'required',
				],
				'description' => [
					'required',
				],
				'expense_amount' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			DB::beginTransaction();
			$employee_cash_check = Employee::select(
				'outlets.amount_eligible',
				'outlets.amount_limit'
			)
				->join('outlets', 'outlets.id', 'employees.outlet_id')
				->where('employees.id', $request->employee_id)->first();

			if ($request->id) {
				$expense_voucher_advance = ExpenseVoucherAdvanceRequest::findOrFail($request->id);
				$expense_voucher_advance->updated_by = Auth::user()->id;
				$expense_voucher_advance->status_id = 3460;
			} else {
				$expense_voucher_advance = new ExpenseVoucherAdvanceRequest;
				$expense_voucher_advance->created_by = Auth::user()->id;
				$expense_voucher_advance->status_id = 3460;
			}
			$expense_voucher_advance->fill($request->all());
			$balence_amount = $request->advance_amount - $request->expense_amount;
			if ($balence_amount) {
				$expense_voucher_advance->balance_amount = $balence_amount;
			} else {
				$expense_voucher_advance->balance_amount = NULL;
			}
			if (isset($request->description)) {
				$expense_voucher_advance->description = $request->description;
			}
			$expense_voucher_advance->save();

			DB::commit();
			$request->session()->flash('success', 'Expense voucher advance saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
