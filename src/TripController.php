<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class TripController extends Controller {
	public function listTrip(Request $r) {
		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),

				// DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				DB::raw('CONCAT(DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y"), " to ", DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(MAX(trips.created_at),"%d/%m/%Y") as created_date'),
				'purpose.name as purpose',
				DB::raw('FORMAT(trips.advance_received,"2","en_IN") as advance_received'),
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)

			->where(function ($query) use ($r) {
				if ($r->get('employee_id')) {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('purpose_id')) {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->groupBy('trips.id')
		// ->orderBy('trips.created_at', 'desc');
			->orderBy('trips.id', 'desc');

		if (!Entrust::can('view-all-trips')) {
			$trips->where('trips.employee_id', Auth::user()->entity_id);
		}
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1_active = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
<a href="#!/eyatra/trip/edit/' . $trip->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a>
				<a href="#!/eyatra/trip/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_trip"
				onclick="angular.element(this).scope().deleteTrip(' . $trip->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';
			})
			->make(true);
	}

	public function tripFormData($trip_id = NULL) {
		return Trip::getTripFormData($trip_id);
	}

	// public function searchCity(Request $c) {
	// 	$key = $c->key;
	// $city_list = NCity::from('ncities')
	// 	->join('nstates as s', 's.id', 'ncities.state_id')
	// 	->select(
	// 		'ncities.id',
	// 		'ncities.name',
	// 		's.name as state_name'
	// 	)
	// 	->where(function ($q) use ($key) {
	// 		$q->where('ncities.name', 'like', '%' . $key . '%')
	// 		;
	// 	})
	// 		->get();
	// 	return response()->json($city_list);
	// }

	public function saveTrip(Request $request) {
		//dd($request->all());
		$size = sizeof($request->visits) - 1;
		for ($i = 0; $i < $size; $i++) {
			//dd($visit);
			if (!(($request->visits[$i]['date'] >= $request->start_date) && ($request->visits[$i]['date'] <= $request->end_date))) {
				return response()->json(['success' => false, 'errors' => "Departure Date Should Be with in Start Date and End Date"]);

			}
			//dump(sizeof($request->visits));
			$next_key = $i + 1;
			if (!($next_key >= $size)) {
				//dump($next_key);
				if ($request->visits[$next_key]['date'] < $request->visits[$i]['date']) {
					return response()->json(['success' => false, 'errors' => "Return Date Should Be Greater Than Or Equal To Departure Date"]);
				}
			}

		}
		//dd('ss');
		return Trip::saveTrip($request);
	}

	public function viewTrip($trip_id) {
		return Trip::getViewData($trip_id);
	}

	public function eyatraTripFilterData() {
		return Trip::getFilterData();
	}

	public function deleteTrip($trip_id) {

		return Trip::deleteTrip($trip_id);

	}

	public function cancelTrip($trip_id) {

		return Trip::cancelTrip($trip_id);
	}

	public function tripVerificationRequest($trip_id) {
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->status_id = 3021;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3080]);
		return response()->json(['success' => true]);
	}

	public function cancelTripVisitBooking($visit_id) {
		return Trip::cancelTripVisitBooking($visit_id);
	}

	public function visitFormData($visit_id) {

		$visit = Visit::find($visit_id);
		if (!$visit) {
			return response()->json(['success' => false, 'errors' => ['Visit not found']]);
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
			'attachments',
			'managerVerificationStatus',
			'trip.employee',
			'trip.purpose',
			'trip.status',
			'trip.lodgings',
			'trip.lodgings.city',
			'trip.lodgings.stateType',
			'trip.boardings',
			'trip.boardings.city',
			'trip.boardings.attachments',
			'trip.localTravels',
			'trip.localTravels.fromCity',
			'trip.localTravels.toCity',
			'trip.localTravels.travelMode',
			'trip.localTravels.attachments',
		];

		//Booking Status
		//3061 => Booking
		//3062 => Cancel

		if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
			$relations[] = 'bookings';
			$relations[] = 'bookings.type';
			$relations[] = 'bookings.travelMode';
			$relations[] = 'bookings.paymentStatus';
		}

		$visit = Visit::with($relations)
			->find($visit_id);

		$this->data['visit'] = $visit;
		$this->data['trip'] = $visit->trip;
		if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
			$this->data['bookings'] = $visit->bookings;
			//dd($this->data['bookings'][0]->total, IND_money_format($this->data['bookings'][0]->total));
		} else {
			$this->data['bookings'] = [];
		}

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function requestCancelVisitBooking($visit_id) {
		return Trip::requestCancelVisitBooking($visit_id);
	}
	public function deleteVisit($visit_id) {
		return Trip::deleteVisit($visit_id);
	}

}
