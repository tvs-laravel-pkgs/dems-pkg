<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\ActivityLog;
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

				// $img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				// $img1_active = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
<a href="#!/eyatra/trip/edit/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
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
		dd($request->all());
		if ($request->departure_date) {

		}
		return Trip::saveTrip($request);
	}

	public function viewTrip($trip_id) {
		return Trip::getViewData($trip_id);
	}

	public function eyatraTripFilterData() {
		return Trip::getFilterData();
	}

	public function deleteTrip($trip_id) {
		//CHECK IF AGENT BOOKED TRIP VISITS
		//dump($trip_id);
		$agent_visits_booked = Visit::where('trip_id', $trip_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
		if ($agent_visits_booked) {
			return response()->json(['success' => false, 'errors' => ['Trip cannot be deleted']]);
		}
		//CHECK IF STATUS IS NEW OR MANAGER REJECTED OR MANAGER APPROVAL PENDING
		$status_exist = Trip::where('id', $trip_id)->whereIn('status_id', [3020, 3021, 3022])->first();
		if (!$status_exist) {
			return response()->json(['success' => false, 'errors' => ['Trip cannot be deleted']]);
		}
		$trip = Trip::where('id', $trip_id)->first();

		$activity['entity_id'] = $trip->id;
		$trip = $trip->forceDelete();

		//$trip = Trip::where('id', $trip_id)->forceDelete();
		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Deleted';
		$activity['activity'] = "delete";
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		return response()->json(['success' => true]);
	}

	public function cancelTrip($trip_id) {

		$trip = Trip::where('id', $trip_id)->update(['status_id' => 3062]);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		//dd($trip);
		$activity['entity_id'] = $trip_id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Cancelled';
		$activity['activity'] = "cancel";
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);
		$visit = Visit::where('trip_id', $trip_id)->update(['status_id' => 3221]);

		return response()->json(['success' => true]);
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
		if ($visit_id) {
			//CHECK IF AGENT BOOKED VISIT
			$agent_visits_booked = Visit::where('id', $visit_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
			if ($agent_visits_booked) {
				return response()->json(['success' => false, 'errors' => ['Visit cannot be deleted']]);
			}
			$visit = Visit::where('id', $visit_id)->first();
			$visit->booking_status_id = 3062; // Booking cancelled
			$visit->save();
			/*$activity['entity_id'] = $visit->id;
				$activity['entity_type'] = 'visit';
				$activity['details'] = 'Visit Booking is Cancelled';
				$activity['activity'] = "cancel";
				//dd($activity);
			*/
			return response()->json(['success' => true]);
		} else {
			return response()->json(['success' => false, 'errors' => ['Bookings not cancelled']]);
		}
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

		$visit = Visit::where('id', $visit_id)->update(['status_id' => 3221]);

		if (!$visit) {
			return response()->json(['success' => false, 'errors' => ['Booking Details not Found']]);
		}

		/*$activity['entity_id'] = $visit->id;
			$activity['entity_type'] = 'visit';
			$activity['details'] = 'Visit Booking cancel request';
			$activity['activity'] = "cancel";
			//dd($activity);
		*/

		return response()->json(['success' => true]);
	}

}
