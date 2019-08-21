<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Uitoux\EYatra\NCity;

class CityController extends Controller {

	public function addTrip(Request $request) {

		$key = $request->all();

		return NCity::getList($request->state_id);
		return response()->json($list);
	}
}
