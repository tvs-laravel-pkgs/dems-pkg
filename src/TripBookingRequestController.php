<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class TripBookingRequestController extends Controller {
	public function listTripBookingRequests(Request $r) {

		$visits = Visit::from('visits as v')
			->join('trips as t', 'v.trip_id', 't.id')
			->join('employees as e', 'e.id', 't.employee_id')
			->join('ncities as fc', 'fc.id', 'v.from_city_id')
			->join('ncities as tc', 'tc.id', 'v.to_city_id')
			->join('entities as tm', 'tm.id', 'v.travel_mode_id')
			->join('configs as bs', 'bs.id', 'v.booking_status_id')
			->join('agents as a', 'a.id', 'v.agent_id')
			->join('configs as status', 'status.id', 'v.status_id')
			->select(
				'v.id',
				't.number',
				'e.code as ecode',
				DB::raw('DATE_FORMAT(v.date,"%d/%m/%Y") as date'),
				'fc.name as from',
				'tc.name as to',
				'tm.name as travel_mode',
				'bs.name as booking_status',
				'a.name as agent',
				'status.name as status'

			)
			->orderBy('t.id', 'desc')
			->orderBy('t.created_at', 'desc')
			->orderBy('v.status_id', 'desc')
		;

		if (!Entrust::can('view-all-trip-booking-requests')) {
			$visits->where('v.agent_id', Auth::user()->entity_id);
		}
		return Datatables::of($visits)
			->addColumn('action', function ($visit) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/trips/booking-requests/view/' . $visit->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
	}

	public function tripBookingRequestsViewData($trip_id) {

		$trip = Trip::with([
			'visits',
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'purpose',
			'status',
		])
			->find($trip_id);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		if (!Entrust::can('trip-verification-all') && $trip->manager_id != Auth::user()->entity_id) {
			return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $start_date->end_date;
		$this->data['trip'] = $trip;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

}
