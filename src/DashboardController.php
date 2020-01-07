<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Trip;

class DashboardController extends Controller {
	public function getDEMSDashboardData(Request $request) {

		// dd($request->all());

		// $outstation_trip_claim_pending = EmployeeClaim::whereNotIn('status_id', [3026, 3031, 3032])->where('employee_id', Auth::user()->entity_id)->count();
		$outstation_total_trip_claim = EmployeeClaim::where('status_id', 3026)->where('employee_id', Auth::user()->entity_id)->count();
		$total_outstation_trips = Trip::where('status_id', '!=', '3032')->where('employee_id', Auth::user()->entity_id)->count();

		$outstation_trip_claim_pending = $total_outstation_trips - $outstation_total_trip_claim;
		dd($total_outstation_trips, $outstation_total_trip_claim, $outstation_trip_claim_pending);
		$this->data['entity_type'] = $entity_type;
		return response()->json($this->data);
	}
}
