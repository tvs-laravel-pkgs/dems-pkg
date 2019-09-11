<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
		$size = sizeof($request->visits);
		for ($i = 0; $i < $size; $i++) {
			//dd($visit);
			if (!(($request->visits[$i]['date'] >= $request->start_date) && ($request->visits[$i]['date'] <= $request->end_date))) {
				return response()->json(['success' => false, 'errors' => "Departure Date Should Be with in Start Date and End Date"]);

			}
			//dump(sizeof($request->visits));
			$next_key = $i + 1;
			if (!($next_key >= $size)) {
				//dump($next_key);
				if ($request->visits[$next_key]['date'] < $request->visits[$i]['date']) {
					return response()->json(['success' => false, 'errors' => "Return Date Should Be Greater Than Or Equal To Departure Date"]);
				}
			}

		}
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

}
