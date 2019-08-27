<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\PettyCash;
use Uitoux\EYatra\PettyCashEmployeeDetails;
use Yajra\Datatables\Datatables;

class PettyCashManagerVerificationController extends Controller {
	public function listPettyCashVerificationManager() {
		$petty_cash = PettyCash::select(
			'petty_cash.id',
			DB::raw('DATE_FORMAT(petty_cash.date , "%d/%m/%Y")as date'),
			'petty_cash.total',
			'employees.name as ename',
			'outlets.name as oname',
			'employees.code as ecode',
			'outlets.code as ocode',
			'configs.name as status'
		)
			->leftJoin('configs', 'configs.id', 'petty_cash.status_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->where('petty_cash.status_id', 3280)
			->where('employees.reporting_to_id', Auth::user()->entity_id)
			->where('employees.company_id', Auth::user()->company_id)
			->orderBy('petty_cash.id', 'desc')
		;

		return Datatables::of($petty_cash)
			->addColumn('action', function ($petty_cash) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/petty-cash/verification1/view/' . $petty_cash->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function pettycashManagerVerificationView($pettycash_id) {
		$this->data['localconveyance'] = $localconveyance_id = Entity::select('id')->where('name', 'LIKE', '%Local Conveyance%')->where('company_id', Auth::user()->company_id)->where('entity_type_id', 512)->first();
		$this->data['petty_cash'] = $petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date'), 'entities.name as expence_type_name', 'purpose.name as purpose_type', 'travel.name as travel_type', 'configs.name as status')
			->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
			->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
			->join('entities as purpose', 'purpose.id', 'petty_cash_employee_details.purpose_id')
			->join('configs', 'configs.id', 'petty_cash.status_id')
			->join('entities as travel', 'travel.id', 'petty_cash_employee_details.travel_mode_id')
			->where('petty_cash.id', $pettycash_id)
			->where('petty_cash_employee_details.expence_type', $localconveyance_id->id)->get();
		// dd($petty_cash);
		$this->data['petty_cash_other'] = $petty_cash_other = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date_other'), 'petty_cash.employee_id', 'employees.name as ename', 'entities.name as other_expence')
			->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
			->where('petty_cash.id', $pettycash_id)
			->where('petty_cash_employee_details.expence_type', '!=', $localconveyance_id->id)->get();

		$this->data['employee'] = $employee = Employee::select(
			'employees.name as name',
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
			->leftjoin('users', 'users.entity_id', 'employees.id')
			->leftjoin('outlets', 'outlets.id', 'employees.outlet_id')
			->leftjoin('employees as emp_manager', 'emp_manager.id', 'employees.reporting_to_id')
			->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
			->leftjoin('lobs', 'lobs.id', 'sbus.lob_id')
			->where('employees.id', $petty_cash_other[0]->employee_id)
			->where('users.company_id', Auth::user()->company_id)
			->first();

		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();

		return response()->json($this->data);
	}

	public function pettycashManagerVerificationSave(Request $request) {
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$petty_cash_manager_approve = PettyCash::where('id', $request->approve)->update(['status_id' => 3281, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				DB::commit();
				return response()->json(['success' => true]);
			} else {
				$petty_cash_manager_reject = PettyCash::where('id', $request->reject)->update(['status_id' => 3282, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				DB::commit();
				return response()->json(['success' => true]);
			}
			$request->session()->flash('success', 'Petty Cash Manager Verification successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
