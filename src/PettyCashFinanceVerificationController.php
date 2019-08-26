<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Storage;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\PettyCash;
use Uitoux\EYatra\PettyCashEmployeeDetails;
use Yajra\Datatables\Datatables;

class PettyCashFinanceVerificationController extends Controller {
	public function listPettyCashVerificationFinance() {
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
			->join('configs', 'configs.id', 'petty_cash.status_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->join('employees as cashier', 'cashier.id', 'outlets.cashier_id')
			->where('petty_cash.status_id', 3281)
			->where('cashier.id', Auth::user()->entity_id)
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
				<a href="#!/eyatra/petty-cash/verification2/view/' . $petty_cash->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}
	public function pettycashfinanceFormData($pettycash_id = NULL) {
		$this->data['localconveyance'] = $localconveyance_id = Entity::select('id')->where('name', 'LIKE', '%Local Conveyance%')->where('company_id', Auth::user()->company_id)->where('entity_type_id', 512)->first();
		if (!$pettycash_id) {
			$petty_cash = new PettyCashEmployeeDetails;
			$petty_cash_other = new PettyCashEmployeeDetails;
			$this->data['action'] = 'Add';
			$this->data['success'] = true;
			$this->data['message'] = 'Petty Cash not found';
			$this->data['employee_list'] = [];
			$this->data['employee'] = '';

		} else {
			$this->data['action'] = 'Edit';
			$petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*',
				DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date'),
				'petty_cash.id as petty_cash_id')
				->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
				->where('petty_cash.id', $pettycash_id)
				->where('petty_cash_employee_details.expence_type', $localconveyance_id->id)->get();
			// dd($petty_cash);
			$petty_cash_other = PettyCashEmployeeDetails::select('petty_cash_employee_details.*',
				DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date_other'),
				'petty_cash.id as petty_cash_id', 'petty_cash.employee_id', 'employees.name as ename', 'entities.name as other_expence')
				->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
				->join('employees', 'employees.id', 'petty_cash.employee_id')
				->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
				->where('petty_cash.id', $pettycash_id)
				->where('petty_cash_employee_details.expence_type', '!=', $localconveyance_id->id)->get();

			if (!$petty_cash) {
				$this->data['success'] = false;
				$this->data['message'] = 'Petty Cash not found';
			}
			$this->data['success'] = true;
			$this->data['employee_list'] = Employee::select('name', 'id', 'code')->get();
			$this->data['employee'] = $employee = Employee::select('employees.name as name', 'employees.code as code', 'designations.name as designation', 'entities.name as grade')
				->leftjoin('designations', 'designations.id', 'employees.designation_id')
				->leftjoin('entities', 'entities.id', 'employees.grade_id')
				->where('employees.id', $petty_cash_other[0]->employee_id)->first();
		}

		$this->data['extras'] = [
			'purpose_list' => Entity::uiPurposeList(),
			'expence_type' => Entity::uiExpenceTypeListBasedPettyCash(),
			'travel_mode_list' => Entity::uiTravelModeList(),
		];
		$this->data['petty_cash'] = $petty_cash;
		$this->data['petty_cash_other'] = $petty_cash_other;
		// dd(Entrust::can('eyatra-indv-expense-vouchers-verification2'));
		$emp_details = [];
		if (Entrust::can('eyatra-indv-expense-vouchers-verification2')) {
			$user_role = 'Cashier';
		} else if (Entrust::can('eyatra-employees')) {
			$user_role = 'Employee';
			$emp_details = Employee::select('entities.name as empgrade', 'employees.name', 'employees.code', 'employees.id as employee_id')->join('entities', 'entities.id', 'employees.grade_id')->where('employees.id', Auth::user()->entity_id)->first();
		}
		$this->data['user_role'] = $user_role;
		$this->data['emp_details'] = $emp_details;
		return response()->json($this->data);
	}

	public function pettycashFinanceVerificationView($pettycash_id) {
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
		// dd(Entrust::can('eyatra-indv-expense-vouchers-verification2'));
		$emp_details = [];
		if (Entrust::can('eyatra-indv-expense-vouchers-verification2')) {
			$user_role = 'Cashier';
		} else {
			$user_role = 'Employee';
			$emp_details = Employee::select('entities.name as empgrade', 'employees.name', 'employees.code', 'employees.id as employee_id')->join('entities', 'entities.id', 'employees.grade_id')->where('employees.id', Auth::user()->entity_id)->first();
		}
		$this->data['user_role'] = $user_role;
		$this->data['emp_details'] = $emp_details;

		$this->data['rejection_list'] = Entity::select('name', 'id')->where('entity_type_id', 511)->where('company_id', Auth::user()->company_id)->get();

		return response()->json($this->data);
	}

	public function pettycashFinanceSave(Request $request) {
		try {
			// $validator = Validator::make($request->all(), [
			// 	'purpose_id' => [
			// 		'required',
			// 	],
			// ]);
			// if ($validator->fails()) {
			// 	return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			// }
			DB::beginTransaction();

			$petty_cash_employee_edit = PettyCash::firstOrNew(['petty_cash.id' => $request->id]);
			if ($request->employee_id) {
				$petty_cash_employee_edit->employee_id = $request->employee_id;
			} else {
				$petty_cash_employee_edit->employee_id = Auth::user()->entity_id;
			}
			$petty_cash_employee_edit->total = $request->claim_total_amount;
			$petty_cash_employee_edit->status_id = 3280;
			$petty_cash_employee_edit->date = Carbon::now();
			$petty_cash_employee_edit->created_by = Auth::user()->id;
			$petty_cash_employee_edit->save();
			if ($request->petty_cash) {
				if (!empty($request->petty_cash_removal_id)) {
					$petty_cash_removal_id = json_decode($request->petty_cash_removal_id, true);
					PettyCashEmployeeDetails::whereIn('id', $petty_cash_removal_id)->delete();
				}
				// dd($expence_type->id);
				foreach ($request->petty_cash as $petty_cash_data) {
					$petty_cash = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data['petty_cash_id']]);
					$petty_cash->petty_cash_id = $petty_cash_employee_edit->id;
					$petty_cash->expence_type = $petty_cash_data['localconveyance'];
					$date = date("Y-m-d", strtotime($petty_cash_data['date']));
					$petty_cash->date = $date;
					$petty_cash->purpose_id = $petty_cash_data['purpose_id'];
					$petty_cash->travel_mode_id = $petty_cash_data['travel_mode_id'];
					$petty_cash->from_place = $petty_cash_data['from_place'];
					$petty_cash->to_place = $petty_cash_data['to_place'];
					$petty_cash->from_km = $petty_cash_data['from_km'];
					$petty_cash->to_km = $petty_cash_data['to_km'];
					$petty_cash->amount = $petty_cash_data['amount'];
					$petty_cash->created_by = Auth::user()->id;
					$petty_cash->created_at = Carbon::now();
					$petty_cash->save();
					//STORE ATTACHMENT
					$item_images = storage_path('petty-cash/localconveyance/attachments/');
					Storage::makeDirectory($item_images, 0777);
					if (!empty($petty_cash_data['attachments'])) {
						foreach ($petty_cash_data['attachments'] as $key => $attachement) {
							$name = $attachement->getClientOriginalName();
							$attachement->move(storage_path('app/public/petty-cash/localconveyance/attachments/'), $name);
							$attachement_petty_cash = new Attachment;
							$attachement_petty_cash->attachment_of_id = 3268;
							$attachement_petty_cash->attachment_type_id = 3200;
							$attachement_petty_cash->entity_id = $petty_cash->id;
							$attachement_petty_cash->name = $name;
							$attachement_petty_cash->save();
						}
					}
				}
			}
			if ($request->petty_cash_other) {
				if (!empty($request->petty_cash_other_removal_id)) {
					$petty_cash_other_removal_id = json_decode($request->petty_cash_other_removal_id, true);
					PettyCashEmployeeDetails::whereIn('id', $petty_cash_other_removal_id)->delete();
				}
				foreach ($request->petty_cash_other as $petty_cash_data_other) {
					$petty_cash_other = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data_other['petty_cash_other_id']]);
					$petty_cash_other->expence_type = $petty_cash_data_other['other_expence'];
					$petty_cash_other->petty_cash_id = $petty_cash_employee_edit->id;
					$date = date("Y-m-d", strtotime($petty_cash_data_other['date_other']));
					$petty_cash_other->date = $date;
					$petty_cash_other->amount = $petty_cash_data_other['amount'];
					$petty_cash_other->tax = $petty_cash_data_other['tax'];
					$petty_cash_other->remarks = $petty_cash_data_other['remarks'];
					$petty_cash_other->created_by = Auth::user()->id;
					$petty_cash_other->created_at = Carbon::now();
					$petty_cash_other->save();
					//STORE ATTACHMENT
					$item_images = storage_path('petty-cash/other/attachments/');
					Storage::makeDirectory($item_images, 0777);
					if (!empty($petty_cash_data_other['attachments'])) {
						foreach ($petty_cash_data_other['attachments'] as $key => $attachement) {
							$name = $attachement->getClientOriginalName();
							$attachement->move(storage_path('app/public/petty-cash/other/attachments/'), $name);
							$attachement_petty_other = new Attachment;
							$attachement_petty_other->attachment_of_id = 3269;
							$attachement_petty_other->attachment_type_id = 3200;
							$attachement_petty_other->entity_id = $petty_cash_other->id;
							$attachement_petty_other->name = $name;
							$attachement_petty_other->save();

						}
					}
				}
			}
			DB::commit();
			$request->session()->flash('success', 'Petty Cash saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function pettycashFinanceVerificationSave(Request $request) {
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$petty_cash_finance_approve = PettyCash::where('id', $request->approve)->update(['status_id' => 3283, 'remarks' => '', 'rejection_id' => NULL]);
				DB::commit();
				return response()->json(['success' => true]);
			} else {
				$petty_cash_finance_reject = PettyCash::where('id', $request->reject)->update(['status_id' => 3284, 'remarks' => $request->remarks, 'rejection_id' => $request->rejection_id]);
				DB::commit();
				return response()->json(['success' => true]);
			}
			$request->session()->flash('success', 'Petty Cash Finance Verification successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
