<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;

class TripController extends Controller {
	public $successStatus = 200;

	public function getTripFormData(Request $r) {
		return Trip::getTripFormData($r->trip_id);
	}

	public function listTrip(Request $request) {
		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.date),"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
		;
		if (!Entrust::can('view-all-trips')) {
			$trips->where('trips.employee_id', Auth::user()->entity_id);
		}

		//FILTERS
		if ($request->number) {
			$trips->where('trips.number', 'like', '%' . $request->number . '%');
		}
		if ($request->from_date && $request->to_date) {
			$trips->where('v.date', '>=', $request->from_date);
			$trips->where('v.date', '<=', $request->to_date);
		} else {
			$today = Carbon::today();
			$from_date = $today->copy()->subMonths(3);
			$to_date = $today->copy()->addMonths(3);
			$trips->where('v.date', '>=', $from_date);
			$trips->where('v.date', '<=', $to_date);
		}

		if ($request->status_ids && count($request->status_ids) > 0) {
			$trips->whereIn('trips.status_id', $request->status_ids);
		} else {
			$trips->whereNotIn('trips.status_id', [3026]);
		}
		if ($request->purpose_ids && count($request->purpose_ids) > 0) {
			$trips->whereIn('trips.purpose_id', $request->purpose_ids);
		}
		if ($request->from_city_id) {
			$trips->whereIn('v.from_city_id', $request->from_city_id);
		}
		if ($request->to_city_id) {
			$trips->whereIn('v.to_city_id', $request->to_city_id);
		}

		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function addTrip(Request $request) {
		// dd($request->all());
		return Trip::saveTrip($request);
	}

	public function viewTrip($trip_id, Request $request) {
		return Trip::getViewData($trip_id);

	}
}
