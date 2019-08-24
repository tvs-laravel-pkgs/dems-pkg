<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;

class TripClaimController extends Controller {
	public $successStatus = 200;

	public function listCompletedTrips(Request $request) {
		$trips = Trip::getEmployeeList();
		$trips = $trips
			->whereRaw('DATE_FORMAT(MAX(v.date),"%d/%m/%Y") < CURDATE()')
			->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function getClaimFormData(Request $request) {
		return Trip::getViewData($trip_id);

	}

	public function saveClaim(Request $r) {
		return Trip::saveTripVerification($r);
	}
}
