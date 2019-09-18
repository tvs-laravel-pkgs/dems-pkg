<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\BankDetail;
use Uitoux\EYatra\ChequeDetail;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Payment;
use Uitoux\EYatra\PettyCash;
use Uitoux\EYatra\PettyCashEmployeeDetails;
use Uitoux\EYatra\WalletDetail;
use Yajra\Datatables\Datatables;

class PettyCashCashierVerificationController extends Controller {
	public function listPettyCashVerificationCashier(Request $r) {
		$petty_cash = PettyCash::select(
			'petty_cash.id',
			DB::raw('DATE_FORMAT(petty_cash.date , "%d/%m/%Y")as date'),
			'petty_cash.total',
			'users.name as ename',
			'outlets.name as oname',
			'employees.code as ecode',
			'employees.id as employee_id',
			'outlets.code as ocode',
			'configs.name as status',
			'petty_cash_type.name as petty_cash_type',
			'petty_cash_type.id as petty_cash_type_id'
		)
			->leftJoin('configs as petty_cash_type', 'petty_cash_type.id', 'petty_cash.petty_cash_type_id')
			->join('configs', 'configs.id', 'petty_cash.status_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('users', 'users.entity_id', 'employees.id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->join('employees as cashier', 'cashier.id', 'outlets.cashier_id')
			->where('petty_cash.status_id', 3281)
			->where('users.user_type_id', 3121)
			->where('cashier.id', Auth::user()->entity_id)
		// ->where('outlets.amount_eligible', 1)
		// ->where('petty_cash.total', '<=', 'outlets.amount_limit')
		// ->where('cashier.id', Auth::user()->entity_id)
			->where('employees.company_id', Auth::user()->company_id)
			->orderBy('petty_cash.id', 'desc')
			->groupBy('petty_cash.id')
			->where(function ($query) use ($r) {
				if (!empty($r->status_id)) {
					$query->where('configs.id', $r->status_id);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->outlet_id)) {
					$query->where('outlets.id', $r->outlet_id);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->employee_id)) {
					$query->where('employees.id', $r->employee_id);
				}
			})
		;

		return Datatables::of($petty_cash)
			->addColumn('action', function ($petty_cash) {

				$type_id = $petty_cash->petty_cash_type_id == '3440' ? 1 : 2;
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
					<a href="#!/eyatra/petty-cash/verification2/view/' . $type_id . '/' . $petty_cash->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a>';
			})
			->make(true);
	}

	public function pettycashCashierVerificationView($type_id, $pettycash_id) {
		$this->data['localconveyance'] = $localconveyance_id = Entity::select('id')->where('name', 'LIKE', '%Local Conveyance%')->where('company_id', Auth::user()->company_id)->where('entity_type_id', 512)->first();
		if ($type_id == 1) {
			$this->data['petty_cash'] = $petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date'), 'entities.name as expence_type_name', 'purpose.name as purpose_type', 'travel.name as travel_type', 'configs.name as status', 'petty_cash.employee_id', 'petty_cash.total', 'employees.payment_mode_id')
				->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
				->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
				->join('employees', 'employees.id', 'petty_cash.employee_id')
				->join('entities as purpose', 'purpose.id', 'petty_cash_employee_details.purpose_id')
				->join('configs', 'configs.id', 'petty_cash.status_id')
				->join('entities as travel', 'travel.id', 'petty_cash_employee_details.travel_mode_id')
				->where('petty_cash.id', $pettycash_id)
				->where('petty_cash_employee_details.expence_type', $localconveyance_id->id)->get();
			$this->data['employee'] = $employee = Employee::select(
				'users.name as name',
				'employees.code as code',
				'designations.name as designation',
				'entities.name as grade',
				'users.mobile_number',
				'outlets.name as outlet_name',
				'sbus.name as sbus_name',
				'lobs.name as lobs_name',
				'emp_manager.name as emp_manager')
				->leftjoin('designations', 'designations.id', 'employees.designation_id')
				->leftjoin('entities', 'entities.id', 'employees.grade_id')
				->leftjoin('users', function ($join) {
					$join->on('users.entity_id', '=', 'employees.id')
						->where('users.user_type_id', 3121);
				})
				->leftjoin('users as emp_manager', function ($join) {
					$join->on('emp_manager.entity_id', '=', 'employees.reporting_to_id')
						->where('emp_manager.user_type_id', 3121);
				})
				->leftjoin('outlets', 'outlets.id', 'employees.outlet_id')
				->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
				->leftjoin('lobs', 'lobs.id', 'sbus.lob_id')
				->orWhere('employees.id', $petty_cash[0]->employee_id)
				->where('users.company_id', Auth::user()->company_id)
				->first();

			$this->data['bank_detail'] = $bank_detail = BankDetail::where('entity_id', $petty_cash[0]->employee_id)->where('detail_of_id', 3121)->first();
			$this->data['cheque_detail'] = $cheque_detail = ChequeDetail::where('entity_id', $petty_cash[0]->employee_id)->where('detail_of_id', 3121)->first();
			$this->data['wallet_detail'] = $wallet_detail = WalletDetail::where('entity_id', $petty_cash[0]->employee_id)->where('wallet_of_id', 3121)->first();
		} elseif ($type_id == 2) {
			// dd($petty_cash);
			$this->data['petty_cash_other'] = $petty_cash_other = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date_other'), 'petty_cash.employee_id', 'entities.name as other_expence', 'petty_cash.total', 'configs.name as status', 'employees.payment_mode_id')
				->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
				->join('employees', 'employees.id', 'petty_cash.employee_id')
				->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
				->join('configs', 'configs.id', 'petty_cash.status_id')
				->where('petty_cash.id', $pettycash_id)
				->where('petty_cash_employee_details.expence_type', '!=', $localconveyance_id->id)->get();
			$this->data['employee'] = $employee = Employee::select(
				'users.name as name',
				'employees.code as code',
				'designations.name as designation',
				'entities.name as grade',
				'users.mobile_number',
				'outlets.name as outlet_name',
				'sbus.name as sbus_name',
				'lobs.name as lobs_name',
				'emp_manager.name as emp_manager')
				->leftjoin('designations', 'designations.id', 'employees.designation_id')
				->leftjoin('entities', 'entities.id', 'employees.grade_id')
				->leftjoin('users', function ($join) {
					$join->on('users.entity_id', '=', 'employees.id')
						->where('users.user_type_id', 3121);
				})
				->leftjoin('users as emp_manager', function ($join) {
					$join->on('emp_manager.entity_id', '=', 'employees.reporting_to_id')
						->where('emp_manager.user_type_id', 3121);
				})
				->leftjoin('outlets', 'outlets.id', 'employees.outlet_id')
				->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
				->leftjoin('lobs', 'lobs.id', 'sbus.lob_id')
				->where('employees.id', $petty_cash_other[0]->employee_id)
				->where('users.company_id', Auth::user()->company_id)
				->first();
			$this->data['bank_detail'] = $bank_detail = BankDetail::where('entity_id', $petty_cash_other[0]->employee_id)->where('detail_of_id', 3121)->first();
			$this->data['cheque_detail'] = $cheque_detail = ChequeDetail::where('entity_id', $petty_cash_other[0]->employee_id)->where('detail_of_id', 3121)->first();
			$this->data['wallet_detail'] = $wallet_detail = WalletDetail::where('entity_id', $petty_cash_other[0]->employee_id)->where('wallet_of_id', 3121)->first();
		}
		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$this->data['payment_mode_list'] = $payment_mode_list;
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);
		$this->data['wallet_mode_list'] = $wallet_mode_list;

		// dd(Entrust::can('eyatra-indv-expense-vouchers-verification2'));
		$emp_details = [];
		if (Entrust::can('eyatra-indv-expense-vouchers-verification2')) {
			$user_role = 'Cashier';
		} else {
			$user_role = 'Employee';
			$emp_details = Employee::select('entities.name as empgrade', 'users.name', 'employees.code', 'employees.id as employee_id')
				->join('entities', 'entities.id', 'employees.grade_id')
				->join('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.id', Auth::user()->entity_id)
				->first();
		}
		$this->data['user_role'] = $user_role;
		$this->data['emp_details'] = $emp_details;

		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();

		return response()->json($this->data);
	}

	public function searchemployee(Request $r) {
		// dd($r->all());
		$key = $r->key;
		$employee_list = Employee::leftJoin('users as emp_user', 'emp_user.entity_id', 'employees.id')->select(
			'emp_user.name',
			'employees.code',
			'employees.id'
		)
			->where(function ($q) use ($key) {
				$q->where('employees.code', 'like', '%' . $key . '%')
					->orWhere('emp_user.name', 'like', '%' . $key . '%')
				;
			})->where('emp_user.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)
			->get();
		// $employee_list = Employee::select('id', 'code')->where('name', 'LIKE', '%' . $r->key . '%')->get();
		return response()->json($employee_list);
	}

	public function pettycashCashierVerificationSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->petty_cash_id) {
				$petty_cash_cashier_approve = PettyCash::where('id', $request->petty_cash_id)->update(['status_id' => 3283, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				//PAYMENT SAVE
				if ($request->type_id == 1) {
					$payment = Payment::firstOrNew(['entity_id' => $request->petty_cash_id, 'payment_of_id' => 3253, 'payment_mode_id' => $request->payment_mode_id]);
					$payment->fill($request->all());
					$payment->date = date('Y-m-d', strtotime($request->date));
					$payment->payment_of_id = 3253;
					// $payment->payment_mode_id = $agent_claim->id;
					$payment->created_by = Auth::user()->id;
					$payment->save();
					$activity['entity_id'] = $request->petty_cash_id;
					$activity['entity_type'] = 'Local Conveyance';
					$activity['details'] = "Claim is paid by Cashier";
					$activity['activity'] = "paid";
					$activity_log = ActivityLog::saveLog($activity);
				} elseif ($request->type_id == 2) {
					$payment = Payment::firstOrNew(['entity_id' => $request->petty_cash_id, 'payment_of_id' => 3254, 'payment_mode_id' => $request->payment_mode_id]);
					$payment->fill($request->all());
					$payment->date = date('Y-m-d', strtotime($request->date));
					$payment->payment_of_id = 3254;
					// $payment->payment_mode_id = $agent_claim->id;
					$payment->created_by = Auth::user()->id;
					$payment->save();
					$activity['entity_id'] = $request->petty_cash_id;
					$activity['entity_type'] = 'Other Expenses';
					$activity['details'] = "Claim is paid by Cashier";
					$activity['activity'] = "paid";
					$activity_log = ActivityLog::saveLog($activity);
				}
				DB::commit();
				return response()->json(['success' => true]);
			} else {
				$petty_cash_cashier_reject = PettyCash::where('id', $request->reject)->update(['status_id' => 3284, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				DB::commit();
				return response()->json(['success' => true]);
			}
			// }
			// $request->session()->flash('success', 'Petty Cash Cashier Verification successfully!');
			// return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
