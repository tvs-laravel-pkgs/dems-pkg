<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Storage;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequest;
use Validator;
use Yajra\Datatables\Datatables;

class ExpenseVoucherAdvanceController extends Controller {
	public function listExpenseVoucherRequest(Request $r) {
		//dd($r->all());
		$expense_voucher_requests = ExpenseVoucherAdvanceRequest::select(
			'expense_voucher_advance_requests.id',
			'users.name as ename',
			'employees.code as ecode',
			'expense_voucher_advance_requests.expense_amount',
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
			->where('employees.id', Auth::user()->entity_id)
			->orderBy('expense_voucher_advance_requests.id', 'desc')
			->groupBy('expense_voucher_advance_requests.id')
			->where(function ($query) use ($r) {
				if (!empty($r->status_id)) {
					$query->where('configs.id', $r->status_id);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->created_date)) {
					$query->where('expense_voucher_advance_requests.date', date("Y-m-d", strtotime($r->created_date)));
				}
			})
		;

		return Datatables::of($expense_voucher_requests)
			->addColumn('action', function ($expense_voucher_requests) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				if ($expense_voucher_requests->status_id == 3460 || $expense_voucher_requests->status_id == 3463 || $expense_voucher_requests->status_id == 3465) {
					return '
				<a href="#!/eyatra/expense/voucher-advance/edit/' . $expense_voucher_requests->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="#!/eyatra/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#expense_voucher_confirm_box"
				onclick="angular.element(this).scope().deleteExpenseVoucher(' . $expense_voucher_requests->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';
				} elseif ($expense_voucher_requests->status_id == 3464 || $expense_voucher_requests->status_id == 3466 || $expense_voucher_requests->status_id == 3469 || $expense_voucher_requests->status_id == 3471) {
					return '
				<a href="#!/eyatra/expense/voucher-advance/edit/' . $expense_voucher_requests->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="#!/eyatra/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
				} else {
					return '<a href="#!/eyatra/expense/voucher-advance/view/' . $expense_voucher_requests->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
				}

			})
			->make(true);
	}

	public function expenseVoucherFormData($id = NULL) {
		//dd('test');
		if (!$id) {
			//dd('sss');
			$this->data['action'] = 'Add';
			$expense_voucher_advance = new ExpenseVoucherAdvanceRequest;
			$this->data['success'] = true;
			$this->data['message'] = 'Expense Voucher not found';
			$this->data['employee_list'] = [];
			$this->data['employee'] = '';
		} else {

			$this->data['action'] = 'Edit';

			$expense_voucher_advance = ExpenseVoucherAdvanceRequest::with([
				'employee',
				'employee.user',
			])
				->where('id', $id)->first();
			$this->data['success'] = true;
		}

		$employee_details = Employee::join('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('users.company_id', Auth::user()->company_id)
			->where('employees.id', Auth::user()->entity_id)
			->select('employees.id')->first();
		$this->data['employee_details'] = $employee_details;
		$this->data['expense_voucher_advance'] = $expense_voucher_advance;
		// dd($expense_voucher_advance);

		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_advance->id)->select('name', 'id')->get();
		$expense_voucher_advance->attachments = $expense_voucher_advance_attachment;

		return response()->json($this->data);
	}

	public function ExpenseVoucherAdvanceFilterData() {
		//$list_of_status = array_merge(Config::ExpenseVoucherAdvanceStatus(), Config::ExpenseVoucherAdvanceStatusList());
		$this->data['status_list'] = $status_list = collect(Config::ExpenseVoucherAdvanceStatus())->prepend(['id' => '', 'name' => 'Select Status']);
		$this->data['employee_list'] = collect(Employee::getEmployeeListBasedCompany())->prepend(['id' => '', 'name' => 'Select Employee']);
		$this->data['outlet_list'] = collect(Outlet::getOutletList())->prepend(['id' => '', 'name' => 'Select Outlet']);

		return response()->json($this->data);
	}
	public function getemployee($searchText) {
		$employee_list = Employee::select('name', 'id', 'code')->where('employees.company_id', Auth::user()->company_id)->where('name', 'LIKE', '%' . $searchText . '%')->orWhere('code', 'LIKE', '%' . $searchText . '%')->get();
		return response()->json(['employee_list' => $employee_list]);
	}

	public function expenseVoucherView($id) {
		$this->data['expense_voucher_view'] = $expense_voucher_view = ExpenseVoucherAdvanceRequest::select(
			'employees.code',
			'users.name',
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.date',
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

		$expense_voucher_advance_attachment = Attachment::where('attachment_of_id', 3442)->where('entity_id', $expense_voucher_view->id)->select('name', 'id')->get();
		$expense_voucher_view->attachments = $expense_voucher_advance_attachment;
		// dd($expense_voucher_view);
		return response()->json($this->data);

	}

	public function expenseVoucherSave(Request $request) {
		// dd($request->all());
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
					'nullable',
				],
				'expense_description' => [
					'nullable',
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

			if (!empty($request->expense_voucher_attach_removal_ids)) {
				$attachment_remove = json_decode($request->expense_voucher_attach_removal_ids, true);
				Attachment::whereIn('id', $attachment_remove)->where('attachment_of_id', 3442)->delete();
			}

			if ($request->id) {
				if ($request->expense_amount) {
					$expense_voucher_advance = ExpenseVoucherAdvanceRequest::findOrFail($request->id);
					$expense_voucher_advance->updated_by = Auth::user()->id;
					$expense_voucher_advance->status_id = 3466;
				} else {
					$expense_voucher_advance = ExpenseVoucherAdvanceRequest::findOrFail($request->id);
					$expense_voucher_advance->updated_by = Auth::user()->id;
					$expense_voucher_advance->status_id = 3460;
				}
			} else {
				$expense_voucher_advance = new ExpenseVoucherAdvanceRequest;
				$expense_voucher_advance->created_by = Auth::user()->id;
				$expense_voucher_advance->status_id = 3460;
			}

			$expense_voucher_advance->fill($request->all());
			$balence_amount = $request->advance_amount - $request->expense_amount;
			if ($request->expense_amount) {
				if ($balence_amount) {
					$expense_voucher_advance->balance_amount = $balence_amount;
				} else {
					$expense_voucher_advance->balance_amount = NULL;
				}
			} else {
				$expense_voucher_advance->balance_amount = NULL;
			}
			if (isset($request->description)) {
				$expense_voucher_advance->description = $request->description;
			}
			if (isset($request->expense_description)) {
				$expense_voucher_advance->expense_description = $request->expense_description;
			}
			$expense_voucher_advance->save();
			//STORE ATTACHMENT
			$item_images = storage_path('expense-voucher-advance/attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (isset($request->attachments)) {
				foreach ($request->attachments as $key => $attachement) {
					if (!empty($attachement)) {
						// $name = $attachement->getClientOriginalName();
						$random_file_name = '_' . $expense_voucher_advance->id . '_Expence_Voucher_Advance_File_' . rand(1, 1000) . '.';
						$extension = $attachement->getClientOriginalExtension();
						$attachement->move(storage_path('app/public/expense-voucher-advance/attachments/'), $random_file_name . $extension);
						$attachement_expense_voucher_advance = new Attachment;
						$attachement_expense_voucher_advance->attachment_of_id = 3442;
						$attachement_expense_voucher_advance->attachment_type_id = 3200;
						$attachement_expense_voucher_advance->entity_id = $expense_voucher_advance->id;
						$attachement_expense_voucher_advance->name = $random_file_name . $extension;
						$attachement_expense_voucher_advance->save();
					}
				}
			}

			DB::commit();
			if ($request->id) {
				return response()->json(['success' => true, 'message' => 'Expense voucher advance updated successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Expense voucher advance saved successfully']);
			}
			// $request->session()->flash('success', 'Expense voucher advance saved successfully!');
			// return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function expenseVoucherDelete($id) {
		// dd($type_id, $pettycash_id);
		$expense_voucher = ExpenseVoucherAdvanceRequest::where('id', $id)->forceDelete();
		if (!$expense_voucher) {
			return response()->json(['success' => false, 'errors' => ['Expense Voucher not found']]);
		}
		return response()->json(['success' => true]);
	}
}
