<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Session;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Trip;

class DashboardController extends Controller {
	public function getDEMSDashboardData(Request $request) {

		if (date('m') >= 4) {
			$start_year = date('Y');
			$end_year = date('Y', strtotime('+1 year'));
		} else {
			$start_year = date('Y', strtotime('-1 year'));
			$end_year = date('Y');
		}

		$fyc_year_session = session('fyc_year_session');
		if (!$fyc_year_session) {
			$fyc_year_session = $start_year . '-' . $end_year;
		}

		$fyc_month_session = session('fyc_month_session');
		if (!$fyc_month_session) {
			$fyc_month_session = date('m', strtotime('-1 month'));
		}
		$this->data['current_fyc'] = $start_year . '-' . $end_year;
		$this->data['fyc_year_session'] = $fyc_year_session;
		$this->data['fyc_month_session'] = $fyc_month_session;

		$split_year = explode('-', $fyc_year_session);
		$first_year = $split_year[0];
		$second_year = $split_year[1];

		if ($fyc_month_session <= 3) {
			$month = date($second_year . '-' . $fyc_month_session . '-01');
		} elseif ($fyc_month_session <= 6) {
			$month = date($first_year . '-' . $fyc_month_session . '-01');
		} elseif ($fyc_month_session <= 9) {
			$month = date($first_year . '-' . $fyc_month_session . '-01');
		} else {
			$month = date($first_year . '-' . $fyc_month_session . '-01');
		}

		$this->data['year_list'] = config('custom.FINANCIAL_YEAR');
		$this->data['month_list'] = config('custom.MONTH_LIST');

		$this->data['start_year'] = $start_year;
		$this->data['end_year'] = $end_year;
		// dd($request->all());

		// $outstation_trip_claim_pending = EmployeeClaim::whereNotIn('status_id', [3026, 3031, 3032])->where('employee_id', Auth::user()->entity_id)->count();
		$outstation_total_trip_claim = EmployeeClaim::where('status_id', 3026)->where('employee_id', Auth::user()->entity_id)->count();
		$total_outstation_trips = Trip::where('status_id', '!=', '3032')->where('employee_id', Auth::user()->entity_id)->count();

		$outstation_trip_claim_pending = $total_outstation_trips - $outstation_total_trip_claim;
		// dd($total_outstation_trips, $outstation_total_trip_claim, $outstation_trip_claim_pending);
		// $this->data['entity_type'] = $entity_type;
		return response()->json($this->data);
	}
}
