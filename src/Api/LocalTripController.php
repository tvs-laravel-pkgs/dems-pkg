<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Uitoux\EYatra\LocalTrip;
use Uitoux\EYatra\Trip;

class LocalTripController extends Controller {
	public $successStatus = 200;

	public function listLocalTrip(Request $request) {
		$trips = LocalTrip::getLocalTripList($request);
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function getTripFormData(Request $request) {
		return LocalTrip::getLocalTripFormData($request->trip_id);
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
			if ($trip->status_id >= 3542) {
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
		return LocalTrip::saveTrip($request);
	}

	public function viewTrip($trip_id, Request $request) {
		return LocalTrip::getViewData($trip_id);
	}

	public function getDashboard() {
		return Trip::getDashboardData();

	}

	public function deleteTrip($trip_id) {
		return LocalTrip::deleteTrip($trip_id);
	}

	public function listTripVerification(Request $r) {
		$trips = LocalTrip::getVerficationPendingList($r);
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);
	}

	public function approveTrip($trip_id) {
		return LocalTrip::approveTrip($trip_id);
	}

	public function rejectTrip(Request $request) {
		return LocalTrip::rejectTrip($request);
	}
}
