<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\AlternateApprove;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\LocalTrip;
use Validator;
use Excel;
use Storage;
use Yajra\Datatables\Datatables;

class LocalTripController extends Controller {
	public function listLocalTrip(Request $r) {

		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename', 'local_trips.status_id',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'purpose.name as purpose',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)

			->where(function ($query) use ($r) {
				if ($r->get('employee_id')) {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('purpose_id')) {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->from_date) {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("local_trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("local_trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('trip_id')) {
					$query->where("local_trips.id", $r->get('trip_id'))->orWhere(DB::raw("-1"), $r->get('trip_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->whereIN('local_trips.status_id', [3021, 3022, 3028, 3032])
		// ->where('local_trips.employee_id', Auth::user()->entity_id)
			->groupBy('local_trips.id')
			->orderBy('local_trips.id', 'desc')
		// ->get()
		;
		if (!Entrust::can('local-view-all-trips')) {
			$trips->where('local_trips.employee_id', Auth::user()->entity_id);
		}

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';

				if ($trip->status_id == '3021' || $trip->status_id == '3022' || $trip->status_id == '3028' || $trip->status_id == '3024' || $trip->status_id == '3032') {
					$edit_class = "visibility:hidden";
					if (Entrust::can('local-trip-edit')) {
						$edit_class = "";
					}
					$delete_class = "visibility:hidden";
					if (Entrust::can('local-trip-delete')) {
						$delete_class = "";
					}
				} else {
					$edit_class = "visibility:hidden";
					$delete_class = "visibility:hidden";
				}

				$action .= '<a style="' . $edit_class . '" href="#!/local-trip/edit/' . $trip->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
					</a> ';

				$action .= '<a href="#!/local-trip/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';

				/*$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_trip"
				onclick="angular.element(this).scope().deleteTrip(' . $trip->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';*/

				return $action;
			})
			->make(true);
	}

	public function listClaimedLocalTrip(Request $r) {

		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.claim_number',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename', 'local_trips.status_id',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'purpose.name as purpose',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)

			->where(function ($query) use ($r) {
				if ($r->get('employee_id')) {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('purpose_id')) {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->from_date) {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("local_trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("local_trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('trip_id')) {
					$query->where("local_trips.id", $r->get('trip_id'))->orWhere(DB::raw("-1"), $r->get('trip_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->whereIN('local_trips.status_id', [3023, 3024, 3026, 3029, 3034, 3035, 3036])
			->where('local_trips.employee_id', Auth::user()->entity_id)
			->groupBy('local_trips.id')
		// ->orderBy('trips.created_at', 'desc');
			->orderBy('local_trips.id', 'desc')
		// ->get()
		;

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';

				if ($trip->status_id != '3026' || $trip->status_id != '3034' || $trip->status_id != '3035' || $trip->status_id == '3023' || $trip->status_id == '3024') {
					$edit_class = "visibility:hidden";
					if (Entrust::can('local-trip-edit')) {
						$edit_class = "";
					}
				} else {
					$edit_class = "visibility:hidden";
				}

				if ($trip->status_id == '3023' || $trip->status_id == '3024') {
					$action .= '<a style="' . $edit_class . '" href="#!/local-trip/claim/edit/' . $trip->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
					</a> ';
				}
				$action .= '<a href="#!/local-trip/claim/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';

				return $action;
			})
			->make(true);
	}

	public function localTripFormData($trip_id = NULL) {

		return LocalTrip::getLocalTripFormData($trip_id);
	}

	public function saveLocalTrip(Request $request) {
		// dd($request->all());
		if ($request->id) {
			$trip_start_date_data = LocalTrip::where('employee_id', Auth::user()->entity_id)
				->where('id', '!=', $request->id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
			$trip = LocalTrip::find($request->id);
			if ($request->local_trip_claim) {
				if ($request->trip_detail == '') {
					return response()->json(['success' => false, 'errors' => "Please enter atleast one local trip expense to further proceed"]);
				}
			}
		} else {
			$trip_start_date_data = LocalTrip::where('employee_id', Auth::user()->entity_id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
		}

		if ($trip_start_date_data) {
			return response()->json(['success' => false, 'errors' => "You have another local trip on this trip period"]);
		}

		if ($request->trip_detail) {
			$size = sizeof($request->trip_detail);
			for ($i = 0; $i < $size; $i++) {
				if (!(($request->trip_detail[$i]['travel_date'] >= $request->start_date) && ($request->trip_detail[$i]['travel_date'] <= $request->end_date))) {
					return response()->json(['success' => false, 'errors' => "Visit date should be within Trip Period"]);
				}
			}
		}
		// elseif ($request->id != NULL) {
		// 	return response()->json(['success' => false, 'errors' => "Please enter atleast one local trip expense to further proceed"]);
		// }

		//Check Local Trip Expense Amount
		if($request->expense_detail){
			$expense_details = array();
			foreach($request->expense_detail as $expense_detail){
				if($expense_detail['amount'] > 0){
					if(isset($expense_details[$expense_detail['expense_date']])){
						$amount = $expense_details[$expense_detail['expense_date']]['amount'] + $expense_detail['amount'];
						$expense_details[$expense_detail['expense_date']]['amount'] = $amount;
					}else{
						$expense_details[$expense_detail['expense_date']]['amount'] = $expense_detail['amount'];
					}
				}else{
					return response()->json(['success' => false, 'errors' => "Expense Amount required"]);
				}
			}

			if(count($expense_details) > 0){
				foreach($expense_details as $expense_detail){
					if(isset($expense_detail['amount']) && $expense_detail['amount'] > 150){
						return response()->json(['success' => false, 'errors' => "Other Expense Amount should not exceed 150"]);
					}
				}
			}
		}
		return LocalTrip::saveTrip($request);
	}

	public function viewLocalTrip($trip_id) {
		return LocalTrip::getViewData($trip_id);
	}

	public function eyatraLocalTripFilterData() {
		return LocalTrip::getFilterData($type = 1);
	}

	public function eyatraLocalTripClaimFilterData() {
		return LocalTrip::getFilterData($type = 4);
	}

	public function eyatraLocalTripVerificationFilterData() {
		return LocalTrip::getFilterData($type = 2);
	}
	public function eyatraLocalTripFinancierVerificationFilterData() {
		return LocalTrip::getFilterData($type = 3);
	}

	public function deleteTrip($trip_id) {
		return LocalTrip::deleteTrip($trip_id);
	}

	public function cancelTrip($trip_id) {
		return LocalTrip::cancelTrip($trip_id);
	}

	public function listLocalTripVerification(Request $r) {

		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.claim_number',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename', 'local_trips.status_id',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'purpose.name as purpose',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)

			->where(function ($query) use ($r) {
				if ($r->get('employee_id')) {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('purpose_id')) {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->from_date) {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("local_trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("local_trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('trip_id')) {
					$query->where("local_trips.id", $r->get('trip_id'))->orWhere(DB::raw("-1"), $r->get('trip_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->whereIN('local_trips.status_id', [3021, 3023])
			->groupBy('local_trips.id')
			->orderBy('local_trips.id', 'desc')
		// ->get()
		;

		$now = date('Y-m-d');
		$sub_employee_id = AlternateApprove::select('employee_id')
			->where('from', '<=', $now)
			->where('to', '>=', $now)
			->where('alternate_employee_id', Auth::user()->entity_id)
			->get()
			->toArray();
		$ids = array_column($sub_employee_id, 'employee_id');
		array_push($ids, Auth::user()->entity_id);
		if (count($sub_employee_id) > 0) {
			$trips = $trips->whereIn('e.reporting_to_id', $ids); //Alternate MANAGER
		} else {
			$trips = $trips->where('e.reporting_to_id', Auth::user()->entity_id); //MANAGER
		}

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';

				if ($trip->status_id < '3023') {
					$action .= '<a href="#!/local-trip/verification/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';
				} else {
					$action .= '<a href="#!/local-trip/verification/detail-view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';
				}
				return $action;
			})
			->make(true);
	}

	public function approveLocalTrip(Request $r) {
		return LocalTrip::approveTrip($r);
	}

	public function rejectLocalTrip(Request $r) {
		return LocalTrip::rejectTrip($r);
	}
	public function listFinancierLocalTripVerification(Request $r) {

		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.claim_number',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename', 'local_trips.status_id',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'purpose.name as purpose',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)

			->where(function ($query) use ($r) {
				if ($r->get('employee_id')) {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('purpose_id')) {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->from_date) {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("local_trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("local_trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('trip_id')) {
					$query->where("local_trips.id", $r->get('trip_id'))->orWhere(DB::raw("-1"), $r->get('trip_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->whereIN('local_trips.status_id', [3030, 3034, 3035])
			->groupBy('local_trips.id')
		// ->orderBy('trips.created_at', 'desc');
			->orderBy('local_trips.id', 'desc')
		// ->get()
		;

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';

				$action .= '<a href="#!/local-trip/financier/verification/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';

				return $action;
			})
			->make(true);
	}

	public function financierApproveLocalTrip(Request $r) {
		try {
			DB::beginTransaction();
			$error_messages = [
				'reference_number.unique' => "Reference Number is already taken",
			];

			$validator = Validator::make($r->all(), [
				'reference_number' => [
					'required:true',
					'unique:payments,reference_number',

				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			$trip = LocalTrip::find($r->trip_id);
			if (!$trip) {
				return response()->json(['success' => false, 'errors' => ['Trip not found']]);
			}

			$trip->status_id = 3026; //PAID
			$trip->save();

			//PAYMENT SAVE
			$payment = Payment::firstOrNew(['payment_of_id' => 3255,'entity_id' => $trip->id]);
			if ($payment->exists) {
				$payment->updated_by = Auth::user()->id;
				$payment->updated_at = Carbon::now();
			} else {
				$payment->created_by = Auth::user()->id;
				$payment->created_at = Carbon::now();
			}
			$payment->fill($r->all());
			$payment->date = date('Y-m-d', strtotime($r->date));
			$payment->payment_of_id = 3255;
			$payment->entity_id = $trip->id;
			// $payment->created_by = Auth::user()->id;
			$payment->save();

			$trip->payment_id = $payment->id;
			$trip->claim_approval_datetime = date('Y-m-d H:i:s');
			$trip->save();

			$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
			$notification = sendnotification($type = 9, $trip, $user, $trip_type = "Local Trip", $notification_type = 'Paid');

			//Claim Approval Log
			$approval_log = ApprovalLog::saveApprovalLog(3582, $trip->id, 3608, Auth::user()->entity_id, Carbon::now());

			DB::commit();
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function financierHoldLocalTrip(Request $r) {
		$trip = LocalTrip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Local Trip not found']]);
		}
		$trip->status_id = 3030;
		$trip->save();

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 8, $trip, $user, $trip_type = "Local Trip", $notification_type = 'Claim Hold');

		return response()->json(['success' => true, 'message' => 'Trip Hold successfully!']);
	}

	public function financierRejectLocalTrip(Request $r) {
		$trip = LocalTrip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Local Trip not found']]);
		}
		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3024;
		$trip->save();

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 7, $trip, $user, $trip_type = "Local Trip", $notification_type = 'Claim Rejected');

		return response()->json(['success' => true, 'message' => 'Trip rejected successfully!']);
	}


	public function import(Request $request) {

		// dd($request->all());
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '-1');
		$empty_rows = 0;
		$data = $request->all();
		$successCount = 0;
		$errorCount = 0;
		$errors = [];
		$error_str = '';
		$validation = Validator::make($request->all(), ['attachment' => 'required']);
		if ($validation->fails()) {
			$response = ['success' => false, 'errors' => 'Please Upload File'];
			return response()->json($response);
		}
		$attachment = 'attachment';
		$attachment_extension = $request->file($attachment)->getClientOriginalExtension();

		if ($attachment_extension != "xlsx" && $attachment_extension != "xls") {
			$response = ['success' => false, 'error' => 'Cannot Read File, Please Import Excel Format File'];
			return response()->json($response);
		}

		$file = $request->file('attachment')->getRealPath();

		$headers = Excel::selectSheetsByIndex(0)->load($file, function ($reader) {
			$reader->takeRows(1);
		})->toArray();
		// dd($headers);
		$mandatory_fields = [
			'trip_type',
			'trip_id',
			'transaction_number',
			'transaction_date',
			'transaction_amount',
		];

		$missing_fields = [];
		foreach ($mandatory_fields as $mandatory_field) {
			if (!array_key_exists($mandatory_field, $headers[0])) {
				$missing_fields[] = $mandatory_field;
			}
		}
		if (count($missing_fields) > 0) {
			$response = ['success' => false, 'error' => "Invalid File, Mandatory fields are missing.", 'missing_fields' => $missing_fields];
			return response()->json($response);
		}

		$destination = config('custom.local_trip_transcation_import_path');

		$timetamp = date('Y_m_d_H_i_s');
		$file_name = $timetamp . '_local_import_file.' . $attachment_extension;
		Storage::makeDirectory($destination, 0777);
		$request->file($attachment)->storeAs($destination, $file_name);
		$output_file = $timetamp . '_local_import_error_file';

		Excel::create($output_file, function ($excel) use ($headers) {
			$excel->sheet('Import Error Report', function ($sheet) use ($headers) {
				$headings = array_keys($headers[0]);
				$headings[] = 'Error No';
				$headings[] = 'Error Details';
				$sheet->fromArray(array($headings));
			});
		})->store('xlsx', storage_path('app/public/trip/local/import/'));

		$total_records = Excel::load(getLocalTranscationImportExcelPath($file_name), function ($reader) {
			$reader->limitColumns(1);
		})->get();
		$total_records = count($total_records);

		$response = [
			'success' => true,
			'total_records' => $total_records,
			'file' => getLocalTranscationImportExcelPath($file_name),
			'outputfile' => 'storage/app/public/trip/local/import/' . $output_file . '.xlsx',
			'error_report_url' => asset(getLocalTranscationImportExcelPath($output_file . '.xlsx')),
			'reference' => $timetamp,
			'errorCount' => $errorCount,
			'successCount' => $successCount,
			'errors' => $error_str,
		];

		return response()->json($response);
	}

	public function chunkImport(Request $request) {
		// dd($request->all());
		$error_str = '';
		$errors = array();
		$status_error_msg = array();
		$error_msg = array();
		$error_count = 0;
		$successCount = 0;
		$newCount = 0;
		$updatedCount = 0;
		$records = 0;
		$empty_rows = 0;
		$file = $request->file;
		$total_records = $request->total_records;
		$skip = $request->skip;
		$records = array();
		$output_file = $request->outputfile;
		$request_client_id = $request->client_id;
		$records_per_request = $request->records_per_request;
		$timetamp = $request->reference;

		try {
			$headers = Excel::selectSheetsByIndex(0)->load($file, function ($reader) use ($skip, $records_per_request) {
				$reader->skipRows($skip)->takeRows($records_per_request);
			})->toArray();
		} catch (\Exception $e) {
			$response = ['success' => false, 'error' => $e->getMessage()];
			return response()->json($response);
		}

		// dd($headers);
		$all_error_records = [];
		$errorCount = 0;

		$k = 0;
		$all_error_records = [];

		foreach ($headers as $key => $trip_detail) {
			$original_record = $trip_detail;
			$k = $skip + $k;
			$skip = false;

			$errors = [];

			if (empty($trip_detail['trip_type'])) {
				$errors[] = 'Trip Type Cannot be empty';
				$skip = true;
			}else{
				if(strtolower($trip_detail['trip_type']) != 'local'){
					$errors[] = 'Invalid Trip Type - ' . $trip_detail['trip_type'];
					$skip = true;
				}
			}

			if (empty($trip_detail['trip_id'])) {
				$errors[] = 'Trip ID Cannot be empty';
				$skip = true;
			} else {
				$trip = LocalTrip::where('number', $trip_detail['trip_id'])->first();
				if (!$trip) {
					$errors[] = 'Invalid Trip ID - ' . $trip_detail['trip_id'];
					$skip = true;
				}
			}
			
			if (empty($trip_detail['transaction_number'])) {
				$errors[] = 'Transaction Number Cannot be empty';
				$skip = true;
			}else{
				$trip = LocalTrip::where('number', $trip_detail['trip_id'])->first();
				if($trip){
					//Check transcation Number unique
					$payment = Payment::where('payment_of_id',3255)->where('reference_number',$trip_detail['transaction_number'])->first();
					if($payment){
						if($payment->entity_id != $trip->id){
							$errors[] = 'Transaction Number has already been taken- ' . $trip_detail['transaction_number'];
							$skip = true;
						}
					}
				}
			}

			if (empty($trip_detail['transaction_date'])) {
				$errors[] = 'Transaction date Cannot be empty';
				$skip = true;
			}

			if (empty($trip_detail['transaction_amount'])) {
				$errors[] = 'Transaction amount Cannot be empty';
				$skip = true;
			}else{
				if(is_numeric($trip_detail['transaction_amount'])){
					$trip = LocalTrip::where('number', $trip_detail['trip_id'])->first();
					if($trip){
						if($trip->claim_amount != $trip_detail['transaction_amount']){
							$errors[] = 'Transaction amount should be equal to the trip amount - ' . $trip_detail['transaction_amount'];
							$skip = true;
						}
					}
				}else{
					$errors[] = 'Invalid Transaction amount - ' . $trip_detail['transaction_amount'];
					$skip = true;
				}
			}

			if (!$skip) {

				$trip = LocalTrip::where('number', $trip_detail['trip_id'])->first();

				if($trip){
					$trip->status_id = 3026; //PAID
					$trip->save();

					//PAYMENT SAVE
					$payment = Payment::firstOrNew(['payment_of_id' => 3255,'entity_id' => $trip->id]);
					if ($payment->exists) {
						$payment->updated_by = Auth::user()->id;
						$payment->updated_at = Carbon::now();
						$updatedCount++;
					} else {
						$payment->created_by = Auth::user()->id;
						$payment->created_at = Carbon::now();
						$newCount++;
					}
					$payment->reference_number = $trip_detail['transaction_number'];
					$payment->amount = $trip_detail['transaction_amount'];
					$payment->payment_mode_id = 3244;
					$payment->date = date('Y-m-d', strtotime($trip_detail['transaction_date']));
					$payment->save();

					$trip->payment_id = $payment->id;
					$trip->claim_approval_datetime = date('Y-m-d H:i:s');
					$trip->save();

					$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
					$notification = sendnotification($type = 9, $trip, $user, $trip_type = "Local Trip", $notification_type = 'Paid');

					//Claim Approval Log
					$approval_log = ApprovalLog::saveApprovalLog(3582, $trip->id, 3608, Auth::user()->entity_id, Carbon::now());

				}
			} else {
				$errorCount++;
				$error_str .= '
                 <div class="mue_errortable_line">
                <span class="mue_ticketerror">Record No:' . ($k + 1) . '</span>
                <span class="mue_rowerror">Reason: ' . implode(',', $errors) . '</span>
                </div>
                    ';
			}

			if (count($errors) > 0) {
				$original_record['Record No'] = $k + 1;
				$original_record['Error Details'] = implode(',', $errors);
				$all_error_records[] = $original_record;
			}
		}

		Excel::load($request->outputfile, function ($excel) use ($all_error_records) {
			$excel->sheet('Import Error Report', function ($sheet) use ($all_error_records) {
				foreach ($all_error_records as $error_record) {
					$sheet->appendRow($error_record, null, 'A1', false, false);
				}
			});
		})->store('xlsx', storage_path('app/public/trip/local/import/'));

		$response = ['success' => true, 'processed' => count($headers), 'errors' => $error_str,
			'newCount' => $newCount, 'updatedCount' => $updatedCount, 'errorCount' => $errorCount];
		return response()->json($response);
	}
	// Updating view status by Karthick T on 21-01-2022
	public function updateAttachmentStatus(Request $r) {
		// dd($r->all());
		$attachment = Attachment::find($r->id);
		if ($attachment) {
			$attachment->view_status = 1;
			$attachment->save();
			$trip_id = $attachment->entity_id;
			
			$approval_status = LocalTrip::validateAttachment($trip_id);
			return response()->json(['success' => true, 'approval_status' => $approval_status]);
		}
		return response()->json(['success' => false]);
	}
	// Updating view status by Karthick T on 21-01-2022

}
