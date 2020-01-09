<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Session;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Trip;

class DashboardController extends Controller {
	public function getDEMSDashboardData(Request $request) {

		if ($request->selected_year) {
			session(['fyc_year_session' => $request->selected_year]);
			$fyc_year_session = $request->selected_year;
		} else {
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

		}

		if ($request->selected_month) {
			session(['fyc_month_session' => $request->selected_month]);
			$fyc_month_session = $request->selected_month;
		} else {
			$fyc_month_session = session('fyc_month_session');
			if (!$fyc_month_session) {
				$fyc_month_session = date('m');
			}

		}

		$this->data['current_fyc'] = $fyc_year_session;
		$this->data['fyc_year_session'] = $fyc_year_session;
		$this->data['fyc_month_session'] = $fyc_month_session;

		if ($fyc_month_session != '-1') {
			$split_year = explode('-', $fyc_year_session);
			$first_year = $split_year[0];
			$second_year = $split_year[1];

			if ($fyc_month_session <= 3) {
				$start_date = date($second_year . '-' . $fyc_month_session . '-01');
			} else {
				$start_date = date($first_year . '-' . $fyc_month_session . '-01');
			}

			$end_date = date('Y-m-t', strtotime($start_date));
		}

		$this->data['outlet_list'] = collect(Outlet::get())->prepend(['id' => '', 'name' => 'All Outlet']);

		$selected_year = explode('-', $fyc_year_session);

		if ($request->selected_month && $request->selected_month != '-1') {
			$this->data['total_outstation_trips'] = $total_outstation_trips = Trip::where('status_id', '!=', '3032')->where('start_date', '>=', $start_date)->where('end_date', '<=', $end_date)->count();
			$this->data['outstation_total_trip_claim'] = $outstation_total_trip_claim = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')->where('ey_employee_claims.status_id', 3026)->where('trips.start_date', '>=', $start_date)->where('trips.end_date', '<=', $end_date)->count();
		} else {
			$start_date = $selected_year[0] . '-04-01';
			$end_date = $selected_year[1] . '-03-31';

			$this->data['total_outstation_trips'] = $total_outstation_trips = Trip::where('status_id', '!=', '3032')->where('start_date', '>=', $start_date)->where('end_date', '<=', $end_date)->count();
			$this->data['outstation_total_trip_claim'] = $outstation_total_trip_claim = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')->where('ey_employee_claims.status_id', 3026)->where('trips.start_date', '>=', $start_date)->where('trips.end_date', '<=', $end_date)->count();
		}
		$this->data['outstation_trip_claim_pending'] = $outstation_trip_claim_pending = $total_outstation_trips - $outstation_total_trip_claim;

		if ($total_outstation_trips > 0) {
			if ($outstation_total_trip_claim > 0) {
				$this->data['outstation_total_trip_claim_percen'] = number_format((float) (($outstation_total_trip_claim / $total_outstation_trips) * 100), 2, '.', '') . "%";
			} else {
				$this->data['outstation_total_trip_claim_percen'] = number_format((float) $outstation_total_trip_claim, 2, '.', '') . "%";
			}
			$this->data['outstation_trip_claim_pending_percen'] = number_format((float) (($outstation_trip_claim_pending / $total_outstation_trips) * 100), 2, '.', '') . "%";
			$this->data['total_outstation_trip_percen'] = number_format((float) 100, 2, '.', '') . "%";

		} else {
			$this->data['outstation_total_trip_claim_percen'] = number_format((float) $total_outstation_trips, 2, '.', '') . "%";
			$this->data['outstation_trip_claim_pending_percen'] = number_format((float) $total_outstation_trips, 2, '.', '') . "%";
			$this->data['total_outstation_trip_percen'] = number_format((float) 0, 2, '.', '') . "%";
		}

		return response()->json($this->data);
	}
}
