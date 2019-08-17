<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Validator;

class TripController extends Controller {
	public $successStatus = 200;

	public function addTrip(Request $request) {
		$validator = Validator::make($request->all(), [
			// 'username' => 'required|string',
			// 'password' => 'required|string',
		]);
		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Reuired parameters missing', 'errors' => $validator->errors()], $this->successStatus);
		}

		$user = Auth::user();

		return response()->json(['success' => true, 'user' => $user], $this->successStatus);

	}
}
