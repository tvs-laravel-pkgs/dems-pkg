<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Storage;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\PettyCash;
use Uitoux\EYatra\PettyCashEmployeeDetails;
use Yajra\Datatables\Datatables;
use App\Http\Controllers\AngularController;
use App\FinancialYear;
use App\SerialNumberGroup;
use Validator;
use Entrust;

class PettyCashController extends Controller {
	public function listPettyCashRequest(Request $r) {
		//dd($r->all());
		if (!empty($r->created_date)) {
			$date = date("Y-m-d", strtotime($r->created_date));
		} else {
			$date = null;
		}
		if (!empty($r->type)) {
			$type = $r->type;
		} else {
			$type = null;
		}

		$petty_cash = PettyCash::select(
			'petty_cash.id',
			'petty_cash.number',
			DB::raw('DATE_FORMAT(petty_cash.date , "%d/%m/%Y")as date'),
			'petty_cash.total',
			'users.name as ename',
			'outlets.name as oname',
			'employees.code as ecode',
			'outlets.code as ocode',
			'configs.name as status',
			'petty_cash.status_id as status_id',
			'petty_cash_type.name as petty_cash_type',
			'petty_cash_type.id as petty_cash_type_id'
		)
			->leftJoin('configs', 'configs.id', 'petty_cash.status_id')
			->leftJoin('configs as petty_cash_type', 'petty_cash_type.id', 'petty_cash.petty_cash_type_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('users', 'users.entity_id', 'employees.id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->where('petty_cash.employee_id', Auth::user()->entity_id)
			->where('users.user_type_id', 3121)
			->orderBy('petty_cash.id', 'desc')
			->groupBy('petty_cash.id')
			->where(function ($query) use ($r) {
				if (!empty($r->status_id)) {
					$query->where('configs.id', $r->status_id);
				}
			})
			->where(function ($query) use ($date) {
				if (!empty($date)) {
					$query->where('petty_cash.date', $date);
				}
			})
			->where(function ($query) use ($type) {
				if (!empty($type)) {
					$query->where('petty_cash_type.id', $type);
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
				if ($petty_cash->status_id == 3280 || $petty_cash->status_id == 3282 || $petty_cash->status_id == 3284) {
				// 	return '
				// <a href="#!/petty-cash/edit/' . $type_id . '/' . $petty_cash->id . '">
				// 	<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				// </a>
				// <a href="#!/petty-cash/view/' . $type_id . '/' . $petty_cash->id . '">
				// 	<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				// </a>';
				/*<a href="javascript:;" data-toggle="modal" data-target="#petty_cash_confirm_box"
				onclick="angular.element(this).scope().deletePettycash(' . $petty_cash->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>*/
	                $action = '';
	                if($type_id == 2 && Entrust::can('eyatra-pcv-edit')){
		                $action .= '<a href="#!/petty-cash/edit/' . $type_id . '/' . $petty_cash->id . '">
							<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
						</a>';
					}

					if($type_id == 2 && Entrust::can('eyatra-pcv-view')){
						$action .= '<a href="#!/petty-cash/view/' . $type_id . '/' . $petty_cash->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>';
					}
					return $action;
				} else {
					if($type_id == 2 && Entrust::can('eyatra-pcv-view')){
						return '<a href="#!/petty-cash/view/' . $type_id . '/' . $petty_cash->id . '">
						<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
					</a>';
				}
				}

			})
			->make(true);
	}

	public function pettycashFormData($type_id = NULL, $pettycash_id = NULL) {
		//GET LOCALCONVEYANCE ID AND NAME
		$this->data['localconveyance'] = $localconveyance_id = Entity::select('id')->where('name', 'LIKE', '%Local Conveyance%')->where('company_id', Auth::user()->company_id)->where('entity_type_id', 512)->first();
		$pcv_request_date_past_days = Config::where('id', 4034)->first()->name;
		$pcv_invoice_date_past_days = Config::where('id', 4035)->first()->name;

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
			//ADD OR EDIT LOCALCONVAYANCE
			if ($type_id == 1) {
				$petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*',
					DB::raw('DATE_FORMAT(petty_cash_employee_details.date,"%d-%m-%Y") as date'),
					'petty_cash.id as petty_cash_id')
					->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
					->where('petty_cash.id', $pettycash_id)
					->where('petty_cash_employee_details.expence_type', $localconveyance_id->id)->get();
				//GET ATTACHMENTS
				foreach ($petty_cash as $key => $value) {
					$petty_cash_attachment = Attachment::where('attachment_of_id', 3440)->where('entity_id', $value->id)->select('name', 'id')->get();
					$value->attachments = $petty_cash_attachment;
				}

			} else {
				$petty_cash = [];
			}

			$this->data['success'] = true;
			//ADD OR EDIT OTHER EXPENSE
			if ($type_id == 2) {
				//OTHER
				$petty_cash_other = PettyCashEmployeeDetails::select('petty_cash_employee_details.*',
					DB::raw('DATE_FORMAT(petty_cash_employee_details.date,"%d-%m-%Y") as date_other'),
					'petty_cash.id as petty_cash_id', 'petty_cash.employee_id', 'users.name as ename', 'entities.name as other_expence')
					->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
					->join('employees', 'employees.id', 'petty_cash.employee_id')
					->join('users', 'users.entity_id', 'employees.id')
					->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
					->where('users.user_type_id', 3121)
					->where('petty_cash.id', $pettycash_id)
					->where('petty_cash_employee_details.expence_type', '!=', $localconveyance_id->id)->get();
				//GET ATTACHMENTS
				foreach ($petty_cash_other as $key => $value) {
					$petty_cash_attachment = Attachment::where('attachment_of_id', 3441)->where('entity_id', $value->id)->select('name', 'id')->get();
					$value->attachments = $petty_cash_attachment;
					$value->invoice = $value->invoice == 1 ? "Yes" : "No";
				}

			} else {
				$petty_cash_other = [];
			}
		}

		$this->data['extras'] = [
			'purpose_list' => Entity::uiPurposeList(),
			'expence_type' => Entity::uiExpenceTypeListBasedPettyCash(),
			'travel_mode_list' => Entity::PettyCashTravelModeList(),
		];
		$this->data['petty_cash'] = $petty_cash;
		$this->data['petty_cash_other'] = $petty_cash_other;
		// dd(Entrust::can('eyatra-indv-expense-vouchers-verification2'));

		//GET AUTH EMPLOYEEE DETAILS
		$user_role = 'Employee';
		$emp_details = Employee::select(
			'users.name as name',
			'employees.code as code',
			'employees.id as emp_id',
			'designations.name as designation',
			'entities.name as grade',
			'users.mobile_number',
			'outlets.name as outlet_name',
			'sbus.name as sbus_name',
			'lobs.name as lobs_name',
			'emp_manager.name as emp_manager',
			'gae.two_wheeler_limit',
			'gae.four_wheeler_limit',
			'gae.two_wheeler_per_km',
			'gae.four_wheeler_per_km',
			'petty_cash.employee_id')
			->leftjoin('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
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
			->leftjoin('petty_cash', 'petty_cash.employee_id', 'employees.id')
			->where('employees.id', Auth::user()->entity_id)
			->where('users.company_id', Auth::user()->company_id)
			->first();
		$this->data['user_role'] = $user_role;
		$this->data['emp_details'] = $emp_details;
		$this->data['pcv_request_date_past_days'] = $pcv_request_date_past_days;
		$this->data['pcv_invoice_date_past_days'] = $pcv_invoice_date_past_days;
		return response()->json($this->data);
	}

	public function fillEmployee($id) {
		if (!empty($id)) {
			$this->data['emp_details'] = $emp_details = Employee::select(
				'entities.name as grade',
				'users.name',
				'employees.code',
				'employees.id as emp_id',
				'configs.name as designation',
				'gae.two_wheeler_per_km',
				'gae.four_wheeler_per_km'
			)
				->join('users', 'users.entity_id', 'employees.id')
				->join('entities', 'entities.id', 'employees.grade_id')
				->join('configs', 'configs.id', 'users.user_type_id')
				->join('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
				->where('employees.id', $id)
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)
				->first();
		} else {
			$this->data['emp_details'] = [];
		}
		// dd($emp_details);
		return response()->json($this->data);
	}

	public function searchEmployee(Request $r) {
		$key = $r->key;
		$this->data['emp_details'] = $emp_details = Employee::select(
			'entities.name as grade',
			'users.name',
			'employees.code',
			'employees.id as emp_id',
			'configs.name as designation',
			'gae.two_wheeler_per_km',
			'gae.four_wheeler_per_km'
		)
			->join('users', 'users.entity_id', 'employees.id')
			->join('entities', 'entities.id', 'employees.grade_id')
			->join('configs', 'configs.id', 'users.user_type_id')
			->join('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
			->where(function ($q) use ($key) {
				$q->where('employees.code', 'like', '%' . $key . '%')
					->orWhere('users.name', 'like', '%' . $key . '%')
				;
			})
			->where('users.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)
			->get();
		// dd($emp_details);
		return response()->json($emp_details);
	}

	public function pettycashView($type_id, $pettycash_id) {
		// dd($type_id, $pettycash_id);

		//GET LOCALCONVEYANCE ID AND NAME
		$this->data['localconveyance'] = $localconveyance_id = Entity::select('id')->where('name', 'LIKE', '%Local Conveyance%')->where('company_id', Auth::user()->company_id)->where('entity_type_id', 512)->first();
		//VIEW LOCALCONVEYANCE
		if ($type_id == 1) {
			$this->data['petty_cash'] = $petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash_employee_details.date,"%d-%m-%Y") as date'), 'entities.name as expence_type_name', 'purpose.name as purpose_type', 'travel.name as travel_type', 'configs.name as status', 'petty_cash.employee_id', 'petty_cash.total')
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

			foreach ($petty_cash as $key => $value) {
				$petty_cash_attachment = Attachment::where('attachment_of_id', 3440)->where('entity_id', $value->id)->select('name', 'id')->get();
				$value->attachments = $petty_cash_attachment;
			}
			//VIEW OTHER EXPENSE
		} elseif ($type_id == 2) {
			// dd($petty_cash);
			$this->data['petty_cash_other'] = $petty_cash_other = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash_employee_details.date,"%d-%m-%Y") as date_other'), 'petty_cash.employee_id', 'entities.name as other_expence', 'petty_cash.total', 'configs.name as status','petty_cash.number')
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
			//GET ATACHMENTS
			foreach ($petty_cash_other as $key => $value) {
				$petty_cash_attachment = Attachment::where('attachment_of_id', 3441)->where('entity_id', $value->id)->select('name', 'id')->get();
				$value->attachments = $petty_cash_attachment;
			}
		}

		return response()->json($this->data);
	}

	//OLD 21 JULY 2023
	// public function pettycashSave(Request $request) {
	// 	//dd($request->all());
	// 	try {
	// 		// $validator = Validator::make($request->all(), [
	// 		// 	'purpose_id' => [
	// 		// 		'required',
	// 		// 	],
	// 		// ]);
	// 		// if ($validator->fails()) {
	// 		// 	return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
	// 		// }
	// 		DB::beginTransaction();

	// 		//GET AMOUNT LIMIT,TWO,FOUR WHEELER AMOUNT PER KM BASED ON EMPLOYEE
	// 		$employee_petty_cash_check = Employee::select(
	// 			'outlets.amount_eligible',
	// 			'outlets.amount_limit',
	// 			'outlets.expense_voucher_limit',
	// 			'gae.two_wheeler_limit',
	// 			'gae.four_wheeler_limit')
	// 			->join('outlets', 'outlets.id', 'employees.outlet_id')
	// 			->join('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
	// 			->where('employees.id', $request->employee_id)->first();

	// 		$get_two_four_wheeler_id = Entity::PettyCashTravelModeList();

	// 		//CHECK VALIDATION FOR MAXIMUM ELEGIBILITY AMOUNT LIMIT
	// 		if ($employee_petty_cash_check->expense_voucher_limit < $request->claim_total_amount) {
	// 			return response()->json(['success' => false, 'errors' => ['The maximum amount limit is ' . $employee_petty_cash_check->expense_voucher_limit]]);
	// 		}

	// 		//ADD PETTY CASH
	// 		$petty_cash_employee_edit = PettyCash::firstOrNew(['petty_cash.id' => $request->id]);
	// 		$petty_cash_employee_edit->employee_id = $request->employee_id;
	// 		$petty_cash_employee_edit->total = $request->claim_total_amount;
	// 		$petty_cash_employee_edit->status_id = 3280;
	// 		$petty_cash_employee_edit->petty_cash_type_id = $request->petty_cash_type_id;
	// 		$petty_cash_employee_edit->date = Carbon::now();
	// 		$petty_cash_employee_edit->created_by = Auth::user()->id;
	// 		$petty_cash_employee_edit->updated_at = NULL;
	// 		$petty_cash_employee_edit->save();

	// 		//ADD LOCALCONVEYANCE
	// 		if ($request->type_id == 1) {
	// 			if ($request->petty_cash) {
	// 				//REMOVE LOCALCONVEYANCE ID LIST
	// 				if (!empty($request->petty_cash_removal_id)) {
	// 					$petty_cash_removal_id = json_decode($request->petty_cash_removal_id, true);
	// 					PettyCashEmployeeDetails::whereIn('id', $petty_cash_removal_id)->delete();

	// 					$attachment_remove = json_decode($request->petty_cash_removal_id, true);
	// 					Attachment::where('entity_id', $attachment_remove)->where('attachment_of_id', 3440)->delete();
	// 				}
	// 				//REMOVE LOCAL CONVEYANCE ATTACHMENT
	// 				if (!empty($request->petty_cash_attach_removal_ids)) {
	// 					$petty_cash_attach_removal_ids = json_decode($request->petty_cash_attach_removal_ids, true);
	// 					Attachment::whereIn('id', $petty_cash_attach_removal_ids)->delete();
	// 				}

	// 				//CHECK TRAVEL MODE,DATE AND DIFFERENCE_KM BASED ON PER DAY KM LIMIT
	// 				foreach ($request->petty_cash as $petty_cash_data) {
	// 					$voucher_km_difference[$petty_cash_data['travel_mode_id']][$petty_cash_data['date']][] = $petty_cash_data['difference_km'];
	// 				}
	// 				foreach ($voucher_km_difference as $travel_mode_id => $date_array) {
	// 					foreach ($date_array as $date_key => $distance_array) {
	// 						//TWO WHEELER
	// 						if ($travel_mode_id == $get_two_four_wheeler_id[0]->id) {
	// 							$total_distance = array_sum($voucher_km_difference[$travel_mode_id][$date_key]);
	// 							if ($total_distance > $employee_petty_cash_check->two_wheeler_limit) {

	// 								return response()->json(['success' => false, 'errors' => ['Maximum Two wheeler distance limit per day is ' . $employee_petty_cash_check->two_wheeler_limit]]);
	// 							}
	// 						}
	// 						//FOUR WHEELER
	// 						if ($travel_mode_id == $get_two_four_wheeler_id[1]->id) {
	// 							$total_distance = array_sum($voucher_km_difference[$travel_mode_id][$date_key]);
	// 							if ($total_distance > $employee_petty_cash_check->four_wheeler_limit) {

	// 								return response()->json(['success' => false, 'errors' => ['Maximum Four wheeler distance limit per day is ' . $employee_petty_cash_check->four_wheeler_limit]]);
	// 							}
	// 						}
	// 					}
	// 				}
	// 				//END CHECK TRAVEL MODE,DATE AND DIFFERENCE_KM BASED ON PER DAY KM LIMIT //

	// 				//ADD LOCALCONVEYANCE TO TABLE
	// 				foreach ($request->petty_cash as $petty_cash_data) {
	// 					$petty_cash = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data['petty_cash_id']]);
	// 					$petty_cash->fill($petty_cash_data);
	// 					//dd($petty_cash_data);
	// 					$petty_cash->remarks=$petty_cash_data['remarks'];
	// 					$petty_cash->petty_cash_id = $petty_cash_employee_edit->id;
	// 					$petty_cash->expence_type = $petty_cash_data['localconveyance'];
	// 					$date = date("Y-m-d", strtotime($petty_cash_data['date']));
	// 					$petty_cash->date = $date;
	// 					$petty_cash->created_by = Auth::user()->id;
	// 					$petty_cash->created_at = Carbon::now();
	// 					$petty_cash->save();
	// 					//STORE ATTACHMENT
	// 					$item_images = storage_path('petty-cash/localconveyance/attachments/');
	// 					Storage::makeDirectory($item_images, 0777);
	// 					if (!empty($petty_cash_data['attachments'])) {
	// 						foreach ($petty_cash_data['attachments'] as $key => $attachement) {
	// 							$random_file_name = $petty_cash->id . '_Localconveyance_file_' . rand(1, 1000) . '.';
	// 							$extension = $attachement->getClientOriginalExtension();
	// 							// dd($name . $extension);
	// 							$attachement->move(storage_path('app/public/petty-cash/localconveyance/attachments/'), $random_file_name . $extension);
	// 							$attachement_petty_cash = new Attachment;
	// 							$attachement_petty_cash->attachment_of_id = 3440;
	// 							$attachement_petty_cash->attachment_type_id = 3200;
	// 							$attachement_petty_cash->entity_id = $petty_cash->id;
	// 							$attachement_petty_cash->name = $random_file_name . $extension;
	// 							$attachement_petty_cash->save();
	// 						}
	// 					}
	// 				}
	// 			} else {
	// 				return response()->json(['success' => false, 'errors' => ['Local Conveyance is empty!']]);
	// 			}
	// 		} else {
	// 			//ADD OTHER EXPENSE
	// 			if ($request->petty_cash_other) {
	// 				//REMOVE OTHER EXPENSE ATTACHMENT
	// 				if (!empty($request->petty_cash_other_attach_removal_ids)) {
	// 					$petty_cash_other_attach_removal_ids = json_decode($request->petty_cash_other_attach_removal_ids, true);
	// 					Attachment::whereIn('id', $petty_cash_other_attach_removal_ids)->delete();
	// 				}
	// 				//REMOVE OTHER EXPENSE IDS
	// 				if (!empty($request->petty_cash_other_removal_id)) {
	// 					$petty_cash_other_removal_id = json_decode($request->petty_cash_other_removal_id, true);
	// 					PettyCashEmployeeDetails::whereIn('id', $petty_cash_other_removal_id)->delete();

	// 					$attachment_remove = json_decode($request->petty_cash_other_removal_id, true);
	// 					Attachment::where('entity_id', $attachment_remove)->where('attachment_of_id', 3441)->delete();
	// 				}
	// 				//ADD OTHER EXPENSE TO TABLE
	// 				foreach ($request->petty_cash_other as $petty_cash_data_other) {
	// 					if($petty_cash_data_other['invoice'] == 1 && empty($petty_cash_data_other['attachments'])){
	// 						return response()->json([
	// 							'success' => false,
	// 							'errors' => ['Kindly upload the proof attachement']
	// 						]);
	// 					}
	// 					$petty_cash_other = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data_other['petty_cash_other_id']]);
	// 					$petty_cash_other->fill($petty_cash_data_other);
	// 					$petty_cash_other->invoice=$petty_cash_data_other['invoice'];
	// 					if($petty_cash_data_other['invoice'] == 1){
	// 					$petty_cash_other->invoice_date=date("Y-m-d", strtotime($petty_cash_data_other['invoice_date']));
	// 					$petty_cash_other->invoice_amount=$petty_cash_data_other['invoice_amount'];
	// 					$petty_cash_other->invoice_number=$petty_cash_data_other['invoice_number'];
	// 				// 	$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($petty_cash_data_other['gstin'],"",false);
	// 		        // //dd($response);
	// 		        // if(!$response['success']){
	// 			    // return response()->json([
    //                 //     'success' => false,
    //                 //     'errors' => [
    //                 //       $response['error']
    //                 //     ],
    //                 // ]);
	// 		        // } 
    //                 //     $petty_cash_other->gstin=$response['gstin'];
    //                 }
	// 					//dd($petty_cash_data_other);
	// 					$petty_cash_other->expence_type = $petty_cash_data_other['other_expence'];
	// 					$petty_cash_other->petty_cash_id = $petty_cash_employee_edit->id;
	// 					$date = date("Y-m-d", strtotime($petty_cash_data_other['date_other']));
	// 					$petty_cash_other->date = $date;
	// 					$petty_cash_other->created_by = Auth::user()->id;
	// 					$petty_cash_other->created_at = Carbon::now();
	// 					$petty_cash_other->save();
	// 					//STORE ATTACHMENT
	// 					$item_images = storage_path('petty-cash/other/attachments/');
	// 					Storage::makeDirectory($item_images, 0777);
	// 					if (!empty($petty_cash_data_other['attachments'])) {
	// 						foreach ($petty_cash_data_other['attachments'] as $key => $attachement) {
	// 							// $name = $attachement->getClientOriginalName();
	// 							$random_file_name = $petty_cash_other->id . '_Other_Expense_file_' . rand(1, 1000) . '.';
	// 							$extension = $attachement->getClientOriginalExtension();
	// 							$attachement->move(storage_path('app/public/petty-cash/other/attachments/'), $random_file_name . $extension);
	// 							$attachement_petty_other = new Attachment;
	// 							$attachement_petty_other->attachment_of_id = 3441;
	// 							$attachement_petty_other->attachment_type_id = 3200;
	// 							$attachement_petty_other->entity_id = $petty_cash_other->id;
	// 							$attachement_petty_other->name = $random_file_name . $extension;
	// 							$attachement_petty_other->save();

	// 						}
	// 					}
	// 				}
	// 			} else {
	// 				return response()->json(['success' => false, 'errors' => ['Other Expense is empty!']]);
	// 			}
	// 		}

	// 		DB::commit();
	// 		if ($request->id) {
	// 			return response()->json(['success' => true, 'message' => 'Petty Cash updated successfully']);
	// 		} else {
	// 			return response()->json(['success' => true, 'message' => 'Petty Cash saved successfully']);
	// 		}
	// 		// $request->session()->flash('success', 'Petty Cash saved successfully!');
	// 		// return response()->json(['success' => true]);
	// 	} catch (Exception $e) {
	// 		DB::rollBack();
	// 		return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
	// 	}
	// }

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

			//GET AMOUNT LIMIT,TWO,FOUR WHEELER AMOUNT PER KM BASED ON EMPLOYEE
			$employee_petty_cash_check = Employee::select(
				'outlets.amount_eligible',
				'outlets.amount_limit',
				'outlets.expense_voucher_limit',
				'gae.two_wheeler_limit',
				'gae.four_wheeler_limit')
				->join('outlets', 'outlets.id', 'employees.outlet_id')
				->join('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
				->where('employees.id', $request->employee_id)->first();

			$get_two_four_wheeler_id = Entity::PettyCashTravelModeList();

			//CHECK VALIDATION FOR MAXIMUM ELEGIBILITY AMOUNT LIMIT
			if ($employee_petty_cash_check->expense_voucher_limit < $request->claim_total_amount) {
				return response()->json(['success' => false, 'errors' => ['The maximum amount limit is ' . $employee_petty_cash_check->expense_voucher_limit]]);
			}

			//ADD PETTY CASH
			$petty_cash_employee_edit = PettyCash::firstOrNew(['petty_cash.id' => $request->id]);
			$petty_cash_employee_edit->company_id = Auth::user()->company_id;
			$petty_cash_employee_edit->employee_id = $request->employee_id;
			$petty_cash_employee_edit->total = $request->claim_total_amount;
			$petty_cash_employee_edit->status_id = 3280;
			$petty_cash_employee_edit->petty_cash_type_id = $request->petty_cash_type_id;
			$petty_cash_employee_edit->date = Carbon::now();
			$petty_cash_employee_edit->created_by = Auth::user()->id;
			$petty_cash_employee_edit->updated_at = NULL;
			$petty_cash_employee_edit->save();
			if(empty($request->id) && $request->petty_cash_type_id == 3441){
				$outlet_id = !empty(Auth::user()->entity->outlet_id) ? Auth::user()->entity->outlet_id : null;
				if (!$outlet_id) {
					return response()->json([
						'success' => false,
						'errors' => 'Outlet not found!'
					]);
				}
	            $financial_year = getFinancialYear();
				$financial_year_id = FinancialYear::where('from', $financial_year)
					->where('company_id', Auth::user()->company_id)
					->pluck('id')
					->first();
				if (!$financial_year_id) {
					return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => [
	                        'Financial Year Not Found',
	                    ],
	                ]);
				}

	            $generate_number = SerialNumberGroup::generateNumber(6, $financial_year_id, $outlet_id);
	            if (!$generate_number['success']) {
	                return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => [
	                        'No PCV Serial number found for FY : ' . $financial_year->from,
	                    ],
	                ]);
	            }

	            $error_messages = [
	                'number.required' => 'Serial number is required',
	                'number.unique' => 'Serial number is already taken',
	            ];
	            $validator = Validator::make($generate_number, [
	                'number' => [
	                    'required',
	                    'unique:petty_cash,number',
	                ],
	            ], $error_messages);
	            if ($validator->fails()) {
	                return response()->json([
	                    'success' => false,
	                    'error' => 'Validation Error',
	                    'errors' => $validator->errors()->all(),
	                ]);
	            }
	            $petty_cash_employee_edit->number = $generate_number['number'];
	            $petty_cash_employee_edit->save();
			}

			//ADD LOCALCONVEYANCE
			if ($request->type_id == 1) {
				if ($request->petty_cash) {
					//REMOVE LOCALCONVEYANCE ID LIST
					if (!empty($request->petty_cash_removal_id)) {
						$petty_cash_removal_id = json_decode($request->petty_cash_removal_id, true);
						PettyCashEmployeeDetails::whereIn('id', $petty_cash_removal_id)->delete();

						$attachment_remove = json_decode($request->petty_cash_removal_id, true);
						Attachment::where('entity_id', $attachment_remove)->where('attachment_of_id', 3440)->delete();
					}
					//REMOVE LOCAL CONVEYANCE ATTACHMENT
					if (!empty($request->petty_cash_attach_removal_ids)) {
						$petty_cash_attach_removal_ids = json_decode($request->petty_cash_attach_removal_ids, true);
						Attachment::whereIn('id', $petty_cash_attach_removal_ids)->delete();
					}

					//CHECK TRAVEL MODE,DATE AND DIFFERENCE_KM BASED ON PER DAY KM LIMIT
					foreach ($request->petty_cash as $petty_cash_data) {
						$voucher_km_difference[$petty_cash_data['travel_mode_id']][$petty_cash_data['date']][] = $petty_cash_data['difference_km'];
					}
					foreach ($voucher_km_difference as $travel_mode_id => $date_array) {
						foreach ($date_array as $date_key => $distance_array) {
							//TWO WHEELER
							if ($travel_mode_id == $get_two_four_wheeler_id[0]->id) {
								$total_distance = array_sum($voucher_km_difference[$travel_mode_id][$date_key]);
								if ($total_distance > $employee_petty_cash_check->two_wheeler_limit) {

									return response()->json(['success' => false, 'errors' => ['Maximum Two wheeler distance limit per day is ' . $employee_petty_cash_check->two_wheeler_limit]]);
								}
							}
							//FOUR WHEELER
							if ($travel_mode_id == $get_two_four_wheeler_id[1]->id) {
								$total_distance = array_sum($voucher_km_difference[$travel_mode_id][$date_key]);
								if ($total_distance > $employee_petty_cash_check->four_wheeler_limit) {

									return response()->json(['success' => false, 'errors' => ['Maximum Four wheeler distance limit per day is ' . $employee_petty_cash_check->four_wheeler_limit]]);
								}
							}
						}
					}
					//END CHECK TRAVEL MODE,DATE AND DIFFERENCE_KM BASED ON PER DAY KM LIMIT //

					//ADD LOCALCONVEYANCE TO TABLE
					foreach ($request->petty_cash as $petty_cash_data) {
						$petty_cash = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data['petty_cash_id']]);
						$petty_cash->fill($petty_cash_data);
						//dd($petty_cash_data);
						$petty_cash->remarks=$petty_cash_data['remarks'];
						$petty_cash->petty_cash_id = $petty_cash_employee_edit->id;
						$petty_cash->expence_type = $petty_cash_data['localconveyance'];
						$date = date("Y-m-d", strtotime($petty_cash_data['date']));
						$petty_cash->date = $date;
						$petty_cash->created_by = Auth::user()->id;
						$petty_cash->created_at = Carbon::now();
						$petty_cash->save();
						//STORE ATTACHMENT
						$item_images = storage_path('petty-cash/localconveyance/attachments/');
						Storage::makeDirectory($item_images, 0777);
						if (!empty($petty_cash_data['attachments'])) {
							foreach ($petty_cash_data['attachments'] as $key => $attachement) {
								$random_file_name = $petty_cash->id . '_Localconveyance_file_' . rand(1, 1000) . '.';
								$extension = $attachement->getClientOriginalExtension();
								// dd($name . $extension);
								$attachement->move(storage_path('app/public/petty-cash/localconveyance/attachments/'), $random_file_name . $extension);
								$attachement_petty_cash = new Attachment;
								$attachement_petty_cash->attachment_of_id = 3440;
								$attachement_petty_cash->attachment_type_id = 3200;
								$attachement_petty_cash->entity_id = $petty_cash->id;
								$attachement_petty_cash->name = $random_file_name . $extension;
								$attachement_petty_cash->save();
							}
						}
					}
				} else {
					return response()->json(['success' => false, 'errors' => ['Local Conveyance is empty!']]);
				}
			} else {
				//ADD OTHER EXPENSE
				if ($request->petty_cash_other) {
					//REMOVE OTHER EXPENSE ATTACHMENT
					if (!empty($request->petty_cash_other_attach_removal_ids)) {
						$petty_cash_other_attach_removal_ids = json_decode($request->petty_cash_other_attach_removal_ids, true);
						Attachment::whereIn('id', $petty_cash_other_attach_removal_ids)->delete();
					}
					//REMOVE OTHER EXPENSE IDS
					if (!empty($request->petty_cash_other_removal_id)) {
						$petty_cash_other_removal_id = json_decode($request->petty_cash_other_removal_id, true);
						PettyCashEmployeeDetails::whereIn('id', $petty_cash_other_removal_id)->delete();

						$attachment_remove = json_decode($request->petty_cash_other_removal_id, true);
						Attachment::where('entity_id', $attachment_remove)->where('attachment_of_id', 3441)->delete();
					}
					//ADD OTHER EXPENSE TO TABLE
					foreach ($request->petty_cash_other as $petty_cash_data_other) {
						if($petty_cash_data_other['invoice'] == 'Yes'){
							if(empty($petty_cash_data_other['petty_cash_other_id'])){
								if(empty($petty_cash_data_other['attachments'])){
									return response()->json([
										'success' => false,
										'errors' => ['Kindly upload the proof attachement']
									]);
								}
							}else{
								$pcv_attachment_count = Attachment::where('attachment_of_id', 3441)
									->where('attachment_type_id', 3200)
									->where('entity_id', $petty_cash_data_other['petty_cash_other_id'])
									->count();
								if($pcv_attachment_count == 0 && empty($petty_cash_data_other['attachments'])){
									return response()->json([
										'success' => false,
										'errors' => ['Kindly upload the proof attachement']
									]);
								}
							}
						}

						$petty_cash_other = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data_other['petty_cash_other_id']]);
						$petty_cash_other->fill($petty_cash_data_other);
						$petty_cash_other->invoice = $petty_cash_data_other['invoice'] == "Yes" ? 1 : 0;
						if($petty_cash_data_other['invoice'] == 'Yes'){
							$petty_cash_other->invoice_date=date("Y-m-d", strtotime($petty_cash_data_other['invoice_date']));
							$petty_cash_other->invoice_amount=$petty_cash_data_other['invoice_amount'];
							$petty_cash_other->invoice_number=$petty_cash_data_other['invoice_number'];
							if(isset($petty_cash_data_other['gstin'])){
								$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($petty_cash_data_other['gstin'],"",false);
						        if(!$response['success']){
								    return response()->json([
				                        'success' => false,
				                        'errors' => [
				                          $response['error']
				                        ],
				                    ]);
						        } 
		                        $petty_cash_other->gstin=$response['gstin'];
							}
                    	}else{
                    		$petty_cash_other->invoice_date = null;
							$petty_cash_other->invoice_amount = null;
							$petty_cash_other->invoice_number = null;
							$petty_cash_other->gstin = null;
							$petty_cash_other->tax = null;

                    	}
						//dd($petty_cash_data_other);
						$petty_cash_other->expence_type = $petty_cash_data_other['other_expence'];
						$petty_cash_other->petty_cash_id = $petty_cash_employee_edit->id;
						$date = date("Y-m-d", strtotime($petty_cash_data_other['date_other']));
						$petty_cash_other->date = $date;
						$petty_cash_other->created_by = Auth::user()->id;
						$petty_cash_other->created_at = Carbon::now();
						$petty_cash_other->save();
						//STORE ATTACHMENT
						$item_images = storage_path('petty-cash/other/attachments/');
						Storage::makeDirectory($item_images, 0777);
						if (!empty($petty_cash_data_other['attachments'])) {
							foreach ($petty_cash_data_other['attachments'] as $key => $attachement) {
								// $name = $attachement->getClientOriginalName();
								$random_file_name = $petty_cash_other->id . '_Other_Expense_file_' . rand(1, 1000) . '.';
								$extension = $attachement->getClientOriginalExtension();
								$attachement->move(storage_path('app/public/petty-cash/other/attachments/'), $random_file_name . $extension);
								$attachement_petty_other = new Attachment;
								$attachement_petty_other->attachment_of_id = 3441;
								$attachement_petty_other->attachment_type_id = 3200;
								$attachement_petty_other->entity_id = $petty_cash_other->id;
								$attachement_petty_other->name = $random_file_name . $extension;
								$attachement_petty_other->save();

							}
						}
					}
				} else {
					return response()->json(['success' => false, 'errors' => ['Other Expense is empty!']]);
				}
			}

			DB::commit();
			if ($request->id) {
				return response()->json(['success' => true, 'message' => 'Petty Cash updated successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Petty Cash saved successfully']);
			}
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function pettycashFilterData() {
		$this->data['status_list'] = $status_list = collect(Config::pettycashStatus())->prepend(['id' => '', 'name' => 'Select Status']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getOutletList())->prepend(['id' => '', 'name' => 'Select Outlet']);
		//dd($this->data['outlet_list']);
		$this->data['employee_list'] = $employee_list = collect(Employee::getEmployeeListBasedCompany())->prepend(['id' => '', 'name' => 'Select Employee']);
		$this->data['petty_cash_type_list'] = collect(Config::select('name', 'id')->where('configs.config_type_id', 527)->where(DB::raw('LOWER(configs.name)'), '!=', strtolower("Advance Expense"))->get())->prepend(['id' => '', 'name' => 'Select Petty Cash Type']);
		return response()->json($this->data);
	}
	public function pettyCashDelete($type_id, $pettycash_id) {
		// dd($type_id, $pettycash_id);
		$petty_cash_emp_details_id = PettyCash::where('id', $pettycash_id)->forceDelete();
		if (!$petty_cash_emp_details_id) {
			return response()->json(['success' => false, 'errors' => ['Petty Cash Employee not found']]);
		}
		return response()->json(['success' => true]);
	}
}
