<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;

class TripClaimController extends Controller {
	public $successStatus = 200;

	public function listCompletedTrips(Request $r) {
		$trips = Trip::getEmployeeList($r);
		$trips = $trips
		// ->whereRaw('MAX(v.date) < CURDATE()')
		->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function getClaimFormData($trip_id) {
		return Trip::getClaimFormData($trip_id);
	}

	public function getClaimViewData($trip_id) {
		return Trip::getClaimViewData($trip_id);
	}

	public function saveClaim(Request $r) {
		return Trip::saveTripVerification($r);
	}
}
