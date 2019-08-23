<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Storage;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\PettyCash;
use Uitoux\EYatra\PettyCashEmployeeDetails;
use Yajra\Datatables\Datatables;

class PettyCashController extends Controller {
	public function listPettyCashRequest(Request $r) {
		$petty_cash = PettyCashEmployeeDetails::select(
			'petty_cash_employee_details.id',
			DB::raw('DATE_FORMAT(petty_cash_employee_details.date , "%d/%m/%Y")as date'),
			'petty_cash_employee_details.total',
			'employees.name as ename',
			'outlets.name as oname',
			'employees.code as ecode',
			'outlets.code as ocode',
		)
			->join('employees', 'employees.id', 'petty_cash_employee_details.employee_id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->orderBy('petty_cash_employee_details.id', 'desc')
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
				<a href="javascript:;" data-toggle="modal" data-target="#delete_emp"
				onclick="angular.element(this).scope().deleteTrip(' . $petty_cash->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->make(true);
	}

	public function pettycashFormData($pettycash_id = NULL) {

		if (!$pettycash_id) {
			$petty_cash = new PettyCash;
			$petty_cash_other = new PettyCash;
			$this->data['action'] = 'Add';
			$this->data['success'] = true;
			$this->data['message'] = 'Petty Cash not found';
			$this->data['employee_list'] = [];
			$this->data['employee'] = '';
		} else {
			$this->data['action'] = 'Edit';
			$petty_cash = PettyCash::select('petty_cash.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date'), 'petty_cash_employee_details.id as petty_cash_employee_details_id')->join('petty_cash_employee_details', 'petty_cash_employee_details.id', 'petty_cash.petty_cash_employee_details_id')
				->where('petty_cash_employee_details.id', $pettycash_id)
				->where('petty_cash.expence_type', 2370)->get();
			// dd($petty_cash);
			$petty_cash_other = PettyCash::select('petty_cash.*', DB::raw('DATE_FORMAT(petty_cash.date,"%d-%m-%Y") as date_other'), 'petty_cash_employee_details.id as petty_cash_employee_details_id', 'petty_cash_employee_details.employee_id', 'employees.name as ename')->join('petty_cash_employee_details', 'petty_cash_employee_details.id', 'petty_cash.petty_cash_employee_details_id')
				->join('employees', 'employees.id', 'petty_cash_employee_details.employee_id')
				->where('petty_cash_employee_details.id', $pettycash_id)
				->where('petty_cash.expence_type', '!=', 2370)->get();
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
			'expence_type' => Entity::uiExpenceTypeList(),
			'travel_mode_list' => Entity::uiTravelModeList(),
		];
		$this->data['petty_cash'] = $petty_cash;
		$this->data['petty_cash_other'] = $petty_cash_other;

		return response()->json($this->data);
	}

	public function getemployee($searchText) {
		$employee_list = Employee::select('name', 'id', 'code')->where('name', 'LIKE', '%' . $searchText . '%')->get();
		return response()->json(['employee_list' => $employee_list]);
	}

	public function pettycashSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();

			$petty_cash_employee_edit = PettyCashEmployeeDetails::firstOrNew(['petty_cash_employee_details.id' => $request->id]);
			$petty_cash_employee_edit->employee_id = $request->employee_id;
			$petty_cash_employee_edit->total = 3000;
			$petty_cash_employee_edit->status = 3240;
			$petty_cash_employee_edit->date = Carbon::now();
			$petty_cash_employee_edit->created_by = Auth::user()->id;
			$petty_cash_employee_edit->save();
			if ($request->petty_cash) {
				if (!empty($request->petty_cash_removal_id)) {
					$petty_cash_removal_id = json_decode($request->petty_cash_removal_id, true);
					PettyCash::whereIn('id', $petty_cash_removal_id)->delete();
				}
				foreach ($request->petty_cash as $petty_cash_data) {
					$petty_cash = PettyCash::firstOrNew(['id' => $petty_cash_data['petty_cash_id'], 'expence_type' => 2370]);
					$petty_cash->petty_cash_employee_details_id = $petty_cash_employee_edit->id;
					$petty_cash->expence_type = $petty_cash_data['localconveyance'];
					$date = date("Y-m-d", strtotime($petty_cash_data['date']));
					$petty_cash->date = $date;
					$petty_cash->purpose_id = $petty_cash_data['purpose_id'];
					$petty_cash->travel_mode_id = $petty_cash_data['travel_mode_id'];
					$petty_cash->from_place = $petty_cash_data['from_place'];
					$petty_cash->to_place = $petty_cash_data['to_place'];
					$petty_cash->from_KM_reading = $petty_cash_data['from_km'];
					$petty_cash->to_KM_reading = $petty_cash_data['to_km'];
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
					PettyCash::whereIn('id', $petty_cash_other_removal_id)->delete();
				}
				foreach ($request->petty_cash_other as $petty_cash_data_other) {
					$petty_cash_other = PettyCash::firstOrNew(['id' => $petty_cash_data_other['petty_cash_other_id'], 'expence_type' => 2371]);
					$petty_cash_other->expence_type = $petty_cash_data_other['other_exoence'];
					$petty_cash_other->petty_cash_employee_details_id = $petty_cash_employee_edit->id;
					$date = date("Y-m-d", strtotime($petty_cash_data_other['date_other']));
					$petty_cash_other->date = $date;
					$petty_cash_other->amount = $petty_cash_data_other['amount'];
					$petty_cash_other->tax = $petty_cash_data_other['tax'];
					$petty_cash_other->details = $petty_cash_data_other['details'];
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
			// }
			DB::commit();
			$request->session()->flash('success', 'Petty Cash saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}
