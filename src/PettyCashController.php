<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\PettyCash;
use Uitoux\EYatra\PettyCashEmployeeDetails;
use Yajra\Datatables\Datatables;

class PettyCashController extends Controller {
	public function listPettyCashRequest(Request $r) {
		$petty_cash = PettyCashEmployeeDetails::select(
			'date',
			'total',
			'employees.name as ename',
			'outlets.name as oname',
			'employees.code, ecode',
			'outlets.code as ocode',
		)
			->join('employees', 'employees.id', 'petty_cash_employee_details.employee_id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->orderBy('petty_cash_employee_details.id', 'desc')
		;

		return Datatables::of($petty_cash)
			->addColumn('action', function ($petty_cash) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
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
			$this->data['success'] = true;
			$this->data['message'] = 'Petty Cash not found';
		} else {
			$petty_cash = PettyCash::with(
				'visits'
			)->find($pettycash_id);
			$petty_cash_other = $petty_cash;
			if (!$petty_cash) {
				$this->data['success'] = false;
				$this->data['message'] = 'Petty Cash not found';
			}
			$this->data['success'] = true;
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

	public function pettycashSave(Request $request) {
		// dump($request->all());
		// dd($request->petty_cash);

		try {

			DB::beginTransaction();
			if (!$request->id) {
				$petty_cash_employee_details = new PettyCashEmployeeDetails;
				$petty_cash_employee_details->employee_id = 10859;
				$petty_cash_employee_details->total = 3000;
				$petty_cash_employee_details->status = 3240;
				$petty_cash_employee_details->date = Carbon::now();
				$petty_cash_employee_details->created_by = Auth::user()->id;
				$petty_cash_employee_details->save();
				if ($request->petty_cash) {
					$petty_cash = new PettyCash;
					foreach ($request->petty_cash as $petty_cash_data) {
						$petty_cash->petty_cash_employee_details_id = $petty_cash_employee_details->id;
						$petty_cash->expence_type = $petty_cash_data['localconveyance'];
						$date = date("Y-m-d", strtotime($petty_cash_data['date']));
						$petty_cash->date = $date;
						$petty_cash->purpose_id = $petty_cash_data['purpose_id'];
						$petty_cash->travel_mode_id = $petty_cash_data['travel_mode_id'];
						$petty_cash->from_place = $petty_cash_data['from_place'];
						$petty_cash->to_place = $petty_cash_data['to_place'];
						$petty_cash->from_KM_reading = $petty_cash_data['from_km'];
						$petty_cash->to_KM_reading = $petty_cash_data['to_km'];
						$petty_cash->created_by = Auth::user()->id;
						$petty_cash->created_at = Carbon::now();
						$petty_cash->updated_at = NULL;
						$petty_cash->save();
					}
				}
				if ($request->petty_cash_other) {
					$petty_cash_other = new PettyCash;
					foreach ($request->petty_cash_other as $petty_cash_data_other) {
						$petty_cash_other->petty_cash_employee_details_id = $petty_cash_employee_details->id;
						$petty_cash_other->expence_type = $petty_cash_data_other['other_exoence'];
						$date = date("Y-m-d", strtotime($petty_cash_data_other['date_other']));
						$petty_cash_other->date = $date;
						$petty_cash_other->amount = $petty_cash_data_other['amount'];
						$petty_cash_other->tax = $petty_cash_data_other['tax'];
						$petty_cash_other->details = $petty_cash_data_other['details'];
						$petty_cash_other->created_by = Auth::user()->id;
						$petty_cash_other->created_at = Carbon::now();
						$petty_cash_other->updated_at = NULL;
						$petty_cash_other->save();
					}
				}
			} else {
				if ($request->petty_cash) {
					$petty_cash = PettyCash::where('id', $request->id)->where('expence_type', 2370);
					foreach ($request->petty_cash as $petty_cash_data) {
						$petty_cash->expence_type = $petty_cash_data['localconveyance'];
						$date = date("Y-m-d", strtotime($petty_cash_data['date']));
						$petty_cash->date = $date;
						$petty_cash->purpose_id = $petty_cash_data['purpose_id'];
						$petty_cash->travel_mode_id = $petty_cash_data['travel_mode_id'];
						$petty_cash->from_place = $petty_cash_data['from_place'];
						$petty_cash->to_place = $petty_cash_data['to_place'];
						$petty_cash->from_KM_reading = $petty_cash_data['from_km'];
						$petty_cash->to_KM_reading = $petty_cash_data['to_km'];
						$petty_cash->updated_by = Auth::user()->id;
						$petty_cash->updated_at = Carbon::now();
						$petty_cash->save();
					}
				}
				if ($request->petty_cash_other) {
					$petty_cash_other = PettyCash::where('id', $request->id)->where('expence_type', 2371);
					foreach ($request->petty_cash_other as $petty_cash_data_other) {
						$petty_cash_other->expence_type = $petty_cash_data_other['other_exoence'];
						$date = date("Y-m-d", strtotime($petty_cash_data_other['date_other']));
						$petty_cash_other->date = $date;
						$petty_cash_other->amount = $petty_cash_data_other['amount'];
						$petty_cash_other->tax = $petty_cash_data_other['tax'];
						$petty_cash_other->details = $petty_cash_data_other['details'];
						$petty_cash_other->updated_by = Auth::user()->id;
						$petty_cash_other->updated_at = Carbon::now();
						$petty_cash_other->save();
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

}
