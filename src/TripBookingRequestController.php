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

	public function tripBookingRequestsViewData($visit_id) {

		$visit = Visit::find($visit_id);
		if (!$visit) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$relations = [
			'type',
			'fromCity',
			'toCity',
			'travelMode',
			'bookingMethod',
			'bookingStatus',
			'agent',
			'status',
			'managerVerificationStatus',
			'trip.employee',
			'trip.purpose',
			'trip.status',
		];
		if ($visit->booking_status_id == 3061) {
			$relations[] = 'bookings';
			$relations[] = 'bookings.type';
			$relations[] = 'bookings.travelMode';
			$relations[] = 'bookings.paymentStatus';
		}

		$visit = Visit::with($relations)
			->find($visit_id);

		if (!Entrust::can('view-all-trip-booking-requests') && $visit->agent_id != Auth::user()->entity_id) {
			return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
		}

		$this->data['visit'] = $visit;
		$this->data['trip'] = $visit->trip;
		if ($visit->booking_status_id == 3061) {
			$this->data['bookings'] = $visit->bookings;
		}
		$this->data['success'] = true;
		return response()->json($this->data);
	}

}
