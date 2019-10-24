<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\Trip;

class TripVerificationController extends Controller {
	public $successStatus = 200;

	public function listTripVerification(Request $r) {
		$trips = Trip::getVerficationPendingList($r);
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function approveTrip($trip_id, Request $r) {
		return Trip::approveTrip($r);
	}

	public function rejectTrip(Request $r) {
		return Trip::rejectTrip($r);
	}

	public function getRejectionData() {
		$reasons = Entity::trip_request_rejection();
		return response()->json(['success' => true, 'reasons' => $reasons]);
	}
}
