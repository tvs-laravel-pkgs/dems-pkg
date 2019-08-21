<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;

class TripVerificationController extends Controller {
	public $successStatus = 200;

	public function listTrip(Request $request) {
		$trips = Trip::getVerficationPendingList();
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function viewTrip($trip_id, Request $request) {
		return Trip::getViewData($trip_id);

	}

	public function saveTripVerification(Request $r) {
		return Trip::saveTripVerification($r);
	}
}
