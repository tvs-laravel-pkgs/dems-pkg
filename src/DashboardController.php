<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Entrust;
use Illuminate\Http\Request;
use Session;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\LocalTrip;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\PettyCash;
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
		if ($request->selected_outlet) {
			session(['outlet_session' => $request->selected_outlet]);
			$outlet_id = $request->selected_outlet;
		} else {
			session(['outlet_session' => '']);
			$outlet_id = '';
		}

		$this->data['current_fyc'] = $fyc_year_session;
		$this->data['fyc_year_session'] = $fyc_year_session;
		$this->data['fyc_month_session'] = $fyc_month_session;

		//ADMIN
		if (Entrust::can('eyatra-masters')) {
			$this->data['outlet_list'] = collect(Outlet::get())->prepend(['id' => '', 'name' => 'All Outlet']);
			$this->data['outlet_show'] = '1';
		} else {
			$this->data['outlet_list'] = collect(Outlet::join('employees', 'employees.outlet_id', 'outlets.id')->where('employees.id', Auth::user()->entity_id)->get());
			$this->data['outlet_show'] = '0';
		}

		$selected_year = explode('-', $fyc_year_session);

		if ($fyc_month_session && $fyc_month_session != '-1') {

			$split_year = explode('-', $fyc_year_session);
			$first_year = $split_year[0];
			$second_year = $split_year[1];

			if ($fyc_month_session <= 3) {
				$start_date = date($second_year . '-' . $fyc_month_session . '-01');
			} else {
				$start_date = date($first_year . '-' . $fyc_month_session . '-01');
			}

			$end_date = date('Y-m-t', strtotime($start_date));

		} else {
			$start_date = $selected_year[0] . '-04-01';
			$end_date = $selected_year[1] . '-03-31';

		}

		$result = $this->trip_details($start_date, $end_date, $outlet_id);

		$this->data['total_outstation_trips'] = $total_outstation_trips = $result['total_outstation_trips']->count();
		$this->data['outstation_total_trip_claim'] = $outstation_total_trip_claim = $result['outstation_total_trip_claim']->count();
		$this->data['outstation_trip_claim_pending'] = $outstation_trip_claim_pending = $total_outstation_trips - $outstation_total_trip_claim;

		$this->data['total_local_trip_count'] = $total_local_trips = $result['total_local_trips']->count();
		$this->data['claimed_local_trip_count'] = $total_local_trip_claim = $result['total_local_trip_claim']->count();
		$this->data['not_claimed_local_trip_count'] = $total_local_trip_claim_pending = $total_local_trips - $total_local_trip_claim;

		$this->data['total_petty_cash_count'] = $total_petty_cash = $result['total_petty_cash']->count();
		$this->data['claimed_petty_cash_count'] = $total_petty_cash_claim = $result['total_petty_cash_claim']->count();
		$this->data['not_claimed_petty_cash_count'] = $total_petty_cash_claim_pending = $total_petty_cash - $total_petty_cash_claim;

		//OUTSTATION TRIP
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

		//LOCAL TRIP
		if ($total_local_trips > 0) {
			if ($total_local_trip_claim > 0) {
				$this->data['claimed_local_trip_per'] = number_format((float) (($total_local_trip_claim / $total_local_trips) * 100), 2, '.', '') . "%";
			} else {
				$this->data['claimed_local_trip_per'] = number_format((float) $total_local_trip_claim, 2, '.', '') . "%";
			}
			$this->data['not_claimed_local_trip_per'] = number_format((float) (($total_local_trip_claim_pending / $total_local_trips) * 100), 2, '.', '') . "%";
			$this->data['total_local_trip_per'] = number_format((float) 100, 2, '.', '') . "%";

		} else {
			$this->data['claimed_local_trip_per'] = number_format((float) $total_local_trips, 2, '.', '') . "%";
			$this->data['not_claimed_local_trip_per'] = number_format((float) $total_local_trips, 2, '.', '') . "%";
			$this->data['total_local_trip_per'] = number_format((float) 0, 2, '.', '') . "%";
		}

		//PETTY CASH
		if ($total_petty_cash > 0) {
			if ($total_petty_cash_claim > 0) {
				$this->data['claimed_petty_cash_per'] = number_format((float) (($total_petty_cash_claim / $total_petty_cash) * 100), 2, '.', '') . "%";
			} else {
				$this->data['claimed_petty_cash_per'] = number_format((float) $total_petty_cash_claim, 2, '.', '') . "%";
			}
			$this->data['not_claimed_petty_cash_per'] = number_format((float) (($total_petty_cash_claim_pending / $total_petty_cash) * 100), 2, '.', '') . "%";
			$this->data['total_petty_cash_per'] = number_format((float) 100, 2, '.', '') . "%";

		} else {
			$this->data['claimed_petty_cash_per'] = number_format((float) $total_petty_cash, 2, '.', '') . "%";
			$this->data['not_claimed_petty_cash_per'] = number_format((float) $total_petty_cash, 2, '.', '') . "%";
			$this->data['total_petty_cash_per'] = number_format((float) 0, 2, '.', '') . "%";
		}

		$split_year = explode('-', $fyc_year_session);
		$first_year = $split_year[0];
		$second_year = $split_year[1];

		$month_list = array();
		$month_list[] = date($first_year . '-04-01');
		$month_list[] = date($first_year . '-05-01');
		$month_list[] = date($first_year . '-06-01');
		$month_list[] = date($first_year . '-07-01');
		$month_list[] = date($first_year . '-08-01');
		$month_list[] = date($first_year . '-09-01');
		$month_list[] = date($first_year . '-10-01');
		$month_list[] = date($first_year . '-11-01');
		$month_list[] = date($first_year . '-12-01');
		$month_list[] = date($second_year . '-01-01');
		$month_list[] = date($second_year . '-02-01');
		$month_list[] = date($second_year . '-03-01');

		//ACHIEVEMENT GRAPH
		$outstation_trip = array();
		$local_trip = array();
		$petty_cash = array();
		foreach ($month_list as $month_value) {
			$start_date = $month_value;
			$end_date = date('Y-m-t', strtotime($start_date));

			$result = $this->trip_details($start_date, $end_date, $outlet_id);

			$outstation_total_trip_claim = $result['outstation_total_trip_claim']->sum('ey_employee_claims.total_amount');
			$local_total_trip_claim = $result['total_local_trip_claim']->sum('local_trips.claim_amount');
			$total_petty_cash_claim = $result['total_petty_cash_claim']->sum('petty_cash.total');

			$outstation_trip[] = (int) $outstation_total_trip_claim;
			$local_trip[] = (int) $local_total_trip_claim;
			$petty_cash[] = (int) $total_petty_cash_claim;
		}

		$this->data['outstation_trip'] = $outstation_trip;
		$this->data['local_trip'] = $local_trip;
		$this->data['petty_cash'] = $petty_cash;

		return response()->json($this->data);
	}

	public function trip_details($start_date, $end_date, $outlet_id) {

		//OUTSTATION TRIP
		$total_outstation_trips = Trip::where('trips.status_id', '!=', '3032')->where('trips.start_date', '>=', $start_date)->where('trips.end_date', '<=', $end_date);
		$outstation_total_trip_claim = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')->where('ey_employee_claims.status_id', 3026)->where('trips.start_date', '>=', $start_date)->where('trips.end_date', '<=', $end_date);

		//LOCAL TRIP
		$total_local_trips = LocalTrip::where('local_trips.status_id', '!=', '3032')->where('local_trips.start_date', '>=', $start_date)->where('local_trips.end_date', '<=', $end_date);
		$total_local_trip_claim = LocalTrip::where('local_trips.status_id', 3026)->where('local_trips.start_date', '>=', $start_date)->where('local_trips.end_date', '<=', $end_date);

		//PETTY CASH
		$total_petty_cash = PettyCash::whereBetween('petty_cash.date', [$start_date, $end_date]);
		$total_petty_cash_claim = PettyCash::whereBetween('petty_cash.date', [$start_date, $end_date])->where('petty_cash.status_id', 3283);

		if ($outlet_id && $outlet_id != '-1') {
			$total_outstation_trips = $total_outstation_trips->leftjoin('employees', 'employees.id', 'trips.employee_id')->where('employees.outlet_id', $outlet_id);
			$outstation_total_trip_claim = $outstation_total_trip_claim->leftjoin('employees', 'employees.id', 'trips.employee_id')->where('employees.outlet_id', $outlet_id);

			$total_local_trips = $total_local_trips->leftjoin('employees', 'employees.id', 'local_trips.employee_id')->where('employees.outlet_id', $outlet_id);
			$total_local_trip_claim = $total_local_trip_claim->leftjoin('employees', 'employees.id', 'local_trips.employee_id')->where('employees.outlet_id', $outlet_id);

			$total_petty_cash = $total_petty_cash->leftjoin('employees', 'employees.id', 'petty_cash.employee_id')->where('employees.outlet_id', $outlet_id);
			$total_petty_cash_claim = $total_petty_cash_claim->leftjoin('employees', 'employees.id', 'petty_cash.employee_id')->where('employees.outlet_id', $outlet_id);

		}

		if (!Entrust::can('eyatra-masters')) {
			$total_outstation_trips = $total_outstation_trips->leftjoin('employees', 'employees.id', 'trips.employee_id')->where('employees.id', Auth::user()->entity_id);
			$outstation_total_trip_claim = $outstation_total_trip_claim->leftjoin('employees', 'employees.id', 'trips.employee_id')->where('employees.id', Auth::user()->entity_id);
			$total_local_trips = $total_local_trips->leftjoin('employees', 'employees.id', 'local_trips.employee_id')->where('employees.id', Auth::user()->entity_id);
			$total_local_trip_claim = $total_local_trip_claim->leftjoin('employees', 'employees.id', 'local_trips.employee_id')->where('employees.id', Auth::user()->entity_id);
			$total_petty_cash = $total_petty_cash->leftjoin('employees', 'employees.id', 'petty_cash.employee_id')->where('employees.id', Auth::user()->entity_id);
			$total_petty_cash_claim = $total_petty_cash_claim->leftjoin('employees', 'employees.id', 'petty_cash.employee_id')->where('employees.id', Auth::user()->entity_id);
		}

		$result = array();

		$result['total_outstation_trips'] = $total_outstation_trips;
		$result['outstation_total_trip_claim'] = $outstation_total_trip_claim;
		$result['total_local_trips'] = $total_local_trips;
		$result['total_local_trip_claim'] = $total_local_trip_claim;
		$result['total_petty_cash'] = $total_petty_cash;
		$result['total_petty_cash_claim'] = $total_petty_cash_claim;

		return $result;

	}
}
