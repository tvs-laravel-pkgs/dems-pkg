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

class PettyCashController extends Controller {
	public function listPettyCashRequest(Request $r) {
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
			->where('petty_cash.employee_id', Auth::user()->entity_id)
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
				<a href="#!/eyatra/petty-cash/edit/' . $petty_cash->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="#!/eyatra/petty-cash/view/' . $petty_cash->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#petty_cash_confirm_box"
				onclick="angular.element(this).scope().deletePettycash(' . $petty_cash->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->make(true);
	}

	public function pettycashFormData($pettycash_id = NULL) {
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
		$emp_details = [];
		if (Entrust::can('eyatra-indv-expense-vouchers-verification2')) {
			$user_role = 'Cashier';
		} else {
			$user_role = 'Employee';
			$emp_details = Employee::where('id', Auth::user()->entity_id)->first();
		}
		$this->data['user_role'] = $user_role;
		$this->data['emp_details'] = $emp_details;
		return response()->json($this->data);
	}

	public function getemployee($searchText) {
		$employee_list = Employee::select('name', 'id', 'code')->where('employees.company_id', Auth::user()->company_id)->where('name', 'LIKE', '%' . $searchText . '%')->orWhere('code', 'LIKE', '%' . $searchText . '%')->get();
		return response()->json(['employee_list' => $employee_list]);
	}

	public function pettycashView($pettycash_id) {
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

		return response()->json($this->data);
	}

	public function pettycashSave(Request $request) {
		// dd($request->all());
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
			$petty_cash_employee_edit->employee_id = $request->employee_id;
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

	public function pettyCashDelete($pettycash_id) {
		$petty_cash_emp_details_id = PettyCashEmployeeDetails::where('id', $pettycash_id)->forceDelete();
		if (!$petty_cash_emp_details_id) {
			return response()->json(['success' => false, 'errors' => ['Petty Cash Employee not found']]);
		}
		return response()->json(['success' => true]);
	}
}
