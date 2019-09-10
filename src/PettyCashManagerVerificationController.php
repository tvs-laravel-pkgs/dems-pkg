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
use Uitoux\EYatra\ReimbursementTranscation;
use Yajra\Datatables\Datatables;

class PettyCashManagerVerificationController extends Controller {
	public function listPettyCashVerificationManager(Request $r) {
		// $type_id = $r->type_id;
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
			'petty_cash_type.id as petty_cash_type_id',
			'users.user_type_id'
		)
			->leftJoin('configs', 'configs.id', 'petty_cash.status_id')
			->leftJoin('configs as petty_cash_type', 'petty_cash_type.id', 'petty_cash.petty_cash_type_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('users', 'users.entity_id', 'employees.id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->where('petty_cash.status_id', 3280)
			->where('users.user_type_id', 3121)
			->where('employees.reporting_to_id', Auth::user()->entity_id)
			->where('employees.company_id', Auth::user()->company_id)
			->orderBy('petty_cash.employee_id', 'desc')
			->groupBy('petty_cash.id')
		;
		// dd($petty_cash->petty_cash_type_id);

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
					<a href="#!/eyatra/petty-cash/verification1/view/' . $type_id . '/' . $petty_cash->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a>';
			})
			->make(true);
	}

	public function pettycashmanagerFormData($type_id = NULL, $pettycash_id = NULL) {
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
			if ($type_id == 1) {
				$petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*',
					DB::raw('DATE_FORMAT(petty_cash_employee_details.date,"%d-%m-%Y") as date'),
					'petty_cash.id as petty_cash_id')
					->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
					->where('petty_cash.id', $pettycash_id)
					->where('petty_cash_employee_details.expence_type', $localconveyance_id->id)->get();
				// if (empty($petty_cash)) {
				// 	$this->data['success'] = false;
				// 	$this->data['message'] = 'Petty Cash not found';
				// }
			} else {
				$petty_cash = [];
			}

			$this->data['success'] = true;

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

			} else {
				$petty_cash_other = [];
			}
		}

		$this->data['extras'] = [
			'purpose_list' => Entity::uiPurposeList(),
			'expence_type' => Entity::uiExpenceTypeListBasedPettyCash(),
			'travel_mode_list' => Entity::uiTravelModeList(),
		];
		$this->data['petty_cash'] = $petty_cash;
		$this->data['petty_cash_other'] = $petty_cash_other;
		// dd(Entrust::can('eyatra-indv-expense-vouchers-verification2'));

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
			'petty_cash.employee_id')
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
		return response()->json($this->data);
	}

	public function pettycashManagerVerificationView($type_id, $pettycash_id) {
		$this->data['localconveyance'] = $localconveyance_id = Entity::select('id')->where('name', 'LIKE', '%Local Conveyance%')->where('company_id', Auth::user()->company_id)->where('entity_type_id', 512)->first();
		if ($type_id == 1) {
			$this->data['petty_cash'] = $petty_cash = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date'), 'entities.name as expence_type_name', 'purpose.name as purpose_type', 'travel.name as travel_type', 'configs.name as status', 'petty_cash.employee_id', 'petty_cash.total')
				->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
				->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
				->join('employees', 'employees.id', 'petty_cash.employee_id')
				->join('entities as purpose', 'purpose.id', 'petty_cash_employee_details.purpose_id')
				->join('configs', 'configs.id', 'petty_cash.status_id')
				->join('entities as travel', 'travel.id', 'petty_cash_employee_details.travel_mode_id')
				->where('petty_cash.id', $pettycash_id)
				->where('petty_cash_employee_details.expence_type', $localconveyance_id->id)->get();
			$this->data['employee'] = $employee = Employee::select(
				'employees.id as employee_id',
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
				->where('employees.id', $petty_cash[0]->employee_id)
				->where('users.company_id', Auth::user()->company_id)
				->first();
		} elseif ($type_id == 2) {
			$this->data['petty_cash_other'] = $petty_cash_other = PettyCashEmployeeDetails::select('petty_cash_employee_details.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date_other'), 'petty_cash.employee_id', 'entities.name as other_expence', 'petty_cash.total', 'configs.name as status')
				->join('petty_cash', 'petty_cash.id', 'petty_cash_employee_details.petty_cash_id')
				->join('employees', 'employees.id', 'petty_cash.employee_id')
				->join('entities', 'entities.id', 'petty_cash_employee_details.expence_type')
				->join('configs', 'configs.id', 'petty_cash.status_id')
				->where('petty_cash.id', $pettycash_id)
				->where('petty_cash_employee_details.expence_type', '!=', $localconveyance_id->id)->get();
			$this->data['employee'] = $employee = Employee::select(
				'users.name as name',
				'employees.id as employee_id',
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
		}
		return response()->json($this->data);
	}
	public function pettycashmanagerSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();

			$petty_cash_employee_edit = PettyCash::firstOrNew(['petty_cash.id' => $request->id]);

			$petty_cash_employee_edit->employee_id = $request->employee_id;
			$petty_cash_employee_edit->total = $request->claim_total_amount;
			$petty_cash_employee_edit->status_id = 3280;
			$petty_cash_employee_edit->petty_cash_type_id = $request->petty_cash_type_id;
			$petty_cash_employee_edit->date = Carbon::now();
			$petty_cash_employee_edit->created_by = Auth::user()->id;
			$petty_cash_employee_edit->updated_at = NULL;
			$petty_cash_employee_edit->save();
			if ($request->petty_cash) {
				if (!empty($request->petty_cash_removal_id)) {
					$petty_cash_removal_id = json_decode($request->petty_cash_removal_id, true);
					PettyCashEmployeeDetails::whereIn('id', $petty_cash_removal_id)->delete();
				}
				// dd($expence_type->id);
				foreach ($request->petty_cash as $petty_cash_data) {
					$petty_cash = PettyCashEmployeeDetails::firstOrNew(['id' => $petty_cash_data['petty_cash_id']]);
					$petty_cash->fill($petty_cash_data);
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
							$name = $attachement->getClientOriginalName();
							$attachement->move(storage_path('app/public/petty-cash/localconveyance/attachments/'), $name);
							$attachement_petty_cash = new Attachment;
							$attachement_petty_cash->attachment_of_id = 3253;
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
					$petty_cash_other->fill($petty_cash_data_other);
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
							$name = $attachement->getClientOriginalName();
							$attachement->move(storage_path('app/public/petty-cash/other/attachments/'), $name);
							$attachement_petty_other = new Attachment;
							$attachement_petty_other->attachment_of_id = 3253;
							$attachement_petty_other->attachment_type_id = 3200;
							$attachement_petty_other->entity_id = $petty_cash_other->id;
							$attachement_petty_other->name = $name;
							$attachement_petty_other->save();

						}
					}
				}
			}

			//Reimbursement Transaction

			$previous_balance_amount = ReimbursementTranscation::where('outlet_id', Auth::user()->entity->outlet_id)->where('company_id', Auth::user()->company_id)->orderBy('id', 'desc')->limit(1)->pluck('balance_amount')->first();
			// dd($previous_balance_amount);
			if ($previous_balance_amount) {
				$balance_amount = $previous_balance_amount - $request->claim_total_amount;
				$reimbursementtranscation = new ReimbursementTranscation;
				$reimbursementtranscation->outlet_id = Auth::user()->entity->outlet_id;
				$reimbursementtranscation->company_id = Auth::user()->company_id;
				if ($request->petty_cash_type_id == 1) {
					$reimbursementtranscation->transcation_id = 3270;
				} else {
					$reimbursementtranscation->transcation_id = 3272;
				}
				$reimbursementtranscation->transaction_date = Carbon::now();
				$reimbursementtranscation->transcation_type = 3272;
				$reimbursementtranscation->amount = $request->claim_total_amount;
				$reimbursementtranscation->balance_amount = $balance_amount;
				$reimbursementtranscation->save();

				//Outlet
				$outlet = Outlet::where('id', Auth::user()->entity->outlet_id)->where('company_id', Auth::user()->company_id)->update(['reimbursement_amount' => $balance_amount]);
			} else {
				// error
			}

			DB::commit();
			$request->session()->flash('success', 'Petty Cash saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function pettycashManagerVerificationSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->approve) {
				$employee_petty_cash_check = Employee::select(
					'outlets.amount_eligible',
					'outlets.amount_limit'
				)
					->join('outlets', 'outlets.id', 'employees.outlet_id')
					->where('employees.id', $request->employee_id)->first();
				if ($employee_petty_cash_check->amount_eligible != 0) {
					if ($employee_petty_cash_check->amount_limit >= $request->amount) {
						$petty_cash_manager_approve = PettyCash::where('id', $request->approve)->update(['status_id' => 3281, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					} else {
						$petty_cash_manager_approve = PettyCash::where('id', $request->approve)->update(['status_id' => 3285, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
					}
				} else {
					$petty_cash_manager_approve = PettyCash::where('id', $request->approve)->update(['status_id' => 3285, 'remarks' => NULL, 'rejection_id' => NULL, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
				}
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
