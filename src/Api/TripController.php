<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Trip;

class TripController extends Controller {
	public $successStatus = 200;

	public function listTrip(Request $r) {
		$trips = Trip::getEmployeeList($r);
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function getTripFormData(Request $r) {
		return Trip::getTripFormData($r->trip_id);
	}

	public function addTrip(Request $request) {
		 //dd($request->all());
       if ($request->advance_received) {
       	    $get_previous_trips = Trip::select('id')
			        ->where('employee_id', Auth::user()->entity_id)
			        ->where('id','!=',$request->id)
			        ->whereIn('advance_request_approval_status_id',[3260,3261])
			        ->whereNotIn('status_id',[3026,3032])
			        ->orderBy('id', 'DESC')->first();
			        //dd($get_previous_trips);
			if ($get_previous_trips) {
				return response()->json(['success' => false, 'errors' => ['Advance Amount Eligible, After All Previous Claim Process Completed']]);
			}
			$get_previous_entry = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')->where('ey_employee_claims.employee_id', Auth::user()->entity_id)->where('ey_employee_claims.status_id', 3031)->orderBy('ey_employee_claims.id', 'DESC')->select('ey_employee_claims.balance_amount')->first();
			if ($get_previous_entry) {
				$previous_amount = $get_previous_entry->balance_amount;
				if ($request->advance_received > $previous_amount) {
					return response()->json(['success' => false, 'errors' => ['Your Previous Trip Claim Amount is Pending.Pay previous trip balance Amount']]);
				} else {

				}
			}
		}

		if ($request->id) {
			$trip_start_date_data = Trip::where('employee_id', Auth::user()->entity_id)
				->where('id', '!=', $request->id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
		} else {
			$trip_start_date_data = Trip::where('employee_id', Auth::user()->entity_id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
		}

		// dd($trip_start_date_data);
		if ($trip_start_date_data) {
			return response()->json(['success' => false, 'errors' => ["You have another trip on this trip period"]]);
		}
		// dd($request->visits);
		$size = sizeof($request->visits);
		for ($i = 0; $i < $size; $i++) {
			if (!(($request->visits[$i]['date'] >= $request->start_date) && ($request->visits[$i]['date'] <= $request->end_date))) {
				return response()->json(['success' => false, 'errors' => ["Departure date should be within Trip Period"]]);

			}

			$next_key = $i + 1;
			if (!($next_key >= $size)) {
				//dump($next_key);
				if ($request->visits[$next_key]['date'] < $request->visits[$i]['date']) {
					return response()->json(['success' => false, 'errors' => ["Return Date Should Be Greater Than Or Equal To Departure Date"]]);
				}
			}

		}

		// // dd($request->advance_received);
		// $size = sizeof($request->visits);
		// for ($i = 0; $i < $size; $i++) {
		// 	//dd($visit);
		// 	if (!(($request->visits[$i]['date'] >= $request->start_date) && ($request->visits[$i]['date'] <= $request->end_date))) {
		// 		return response()->json(['success' => false, 'errors' => "Departure Date Should Be with in Start Date and End Date"]);

		// 	}
		// 	//dump(sizeof($request->visits));
		// 	$next_key = $i + 1;
		// 	if (!($next_key >= $size)) {
		// 		//dump($next_key);
		// 		if ($request->visits[$next_key]['date'] < $request->visits[$i]['date']) {
		// 			return response()->json(['success' => false, 'errors' => "Return Date Should Be Greater Than Or Equal To Departure Date"]);
		// 		}
		// 	}

		// }
		return Trip::saveTrip($request);
	}

	public function viewTrip($trip_id, Request $request) {
		return Trip::getViewData($trip_id);

	}

	public function deleteTrip($trip_id) {
		return Trip::deleteTrip($trip_id);
	}

	public function cancelTrip(Request $r) {

		return Trip::cancelTrip($r);
	}
	public function requestCancelVisitBooking($visit_id) {
		return Trip::requestCancelVisitBooking($visit_id);
	}
	public function cancelTripVisitBooking($visit_id) {
		return Trip::cancelTripVisitBooking($visit_id);
	}
	public function deleteVisit($visit_id) {
		return Trip::deleteVisit($visit_id);
	}

}
