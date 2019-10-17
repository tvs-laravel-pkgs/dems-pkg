<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Uitoux\EYatra\Employee;
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
		// dd($request->all());
		if ($request->advance_received) {
			$check_trip_amount_eligible = Employee::select('gae.travel_advance_limit')
				->leftJoin('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')->first();
			if ($check_trip_amount_eligible->travel_advance_limit < $request->advance_received) {
				return response()->json(['success' => false, 'errors' => ['Maximum Eligibility Advance Amount is ' . $check_trip_amount_eligible->travel_advance_limit]]);
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
			return response()->json(['success' => false, 'errors' => "You have another trip on this trip period"]);
		}
		// dd($request->visits);
		$size = sizeof($request->visits);
		for ($i = 0; $i < $size; $i++) {
			if (!(($request->visits[$i]['date'] >= $request->start_date) && ($request->visits[$i]['date'] <= $request->end_date))) {
				return response()->json(['success' => false, 'errors' => "Departure date should be within Trip Period"]);

			}

			$next_key = $i + 1;
			if (!($next_key >= $size)) {
				//dump($next_key);
				if ($request->visits[$next_key]['date'] < $request->visits[$i]['date']) {
					return response()->json(['success' => false, 'errors' => "Return Date Should Be Greater Than Or Equal To Departure Date"]);
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

	public function getDashboard() {
		return Trip::getDashboardData();

	}

	public function deleteTrip($trip_id) {
		return Trip::deleteTrip($trip_id);
	}

	public function cancelTrip($trip_id) {

		return Trip::cancelTrip($trip_id);
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
