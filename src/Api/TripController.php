<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;

class TripController extends Controller {
	public $successStatus = 200;

	public function addTrip(Request $request) {
		// dd($request->all());
		return Trip::saveTrip($request);
	}
}
