<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class TripController extends Controller {
	public function listTrip(Request $r) {

		$trips = Trip::from('trips')
			->leftjoin('visits as v', 'v.trip_id', 'trips.id')
			->leftjoin('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->leftjoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id') //NEED TO ENABLE
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename', 'trips.status_id',
				'trips.approve_remarks',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),

				// DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(trips.created_at,"%d-%m-%Y") as created_date'),
				'purpose.name as purpose',
				DB::raw('IF((trips.advance_received) IS NULL,"0.00",FORMAT(trips.advance_received,"2","en_IN")) as advance_received'),
				DB::raw('IF((trips.reason) IS NULL,"--",trips.reason) as reason'),
				'status.name as status',
				'ey_employee_claims.id as claim_id' //NEED TO ENABLE
			)
			->where('e.company_id', Auth::user()->company_id)
			->where('e.business_id', Auth::user()->business_id)
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
				if ($r->from_date) {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('trip_id')) {
					$query->where("trips.id", $r->get('trip_id'))->orWhere(DB::raw("-1"), $r->get('trip_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->whereIN('trips.status_id', [3021, 3022, 3028, 3032, 3038, 3039, 3033])
		// ->where('trips.employee_id', Auth::user()->entity_id)
			->groupBy('trips.id')
		// ->orderBy('trips.created_at', 'desc');
			->orderBy('trips.id', 'desc');

		if (!Entrust::can('view-all-trips')) {
			$trips->where('trips.employee_id', Auth::user()->entity_id);
		}
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';

				// if ($trip->status_id == '3032' && !empty($trip->approve_remarks) || $trip->status_id == '3021' || $trip->status_id == '3022' || $trip->status_id == '3028') {
				// if (($trip->status_id == '3032' && !empty($trip->approve_remarks)) || $trip->status_id == '3021' || $trip->status_id == '3022') { //NEED TO DISABLE
				// if (($trip->status_id == '3032' || $trip->status_id == '3021' || $trip->status_id == '3022') || ($trip->status_id == '3028' && !$trip->claim_id)) { //NEED TO ENABLE
				if ($trip->status_id == '3021' || $trip->status_id == '3022' || $trip->status_id == '3028' && $trip->advance_received == '0' || $trip->status_id == '3033' && $trip->advance_received == '0') { 
					//dd($trip->advance_received);
					$edit_class = "visibility:hidden";
					if (Entrust::can('trip-edit')) {
						$edit_class = "";
					}
					$delete_class = "visibility:hidden";
					if (Entrust::can('trip-delete')) {
						$delete_class = "";
					}
				} else {

					$edit_class = "visibility:hidden";
					$delete_class = "visibility:hidden";
				}

				$action .= '<a style="' . $edit_class . '" href="#!/trip/edit/' . $trip->id . '">
					<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
				</a> ';
				$action .= '<a href="#!/trip/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';
				/*$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_trip"
									onclick="angular.element(this).scope().deleteTrip(' . $trip->id . ')" dusk = "delete-btn" title="Delete">
					                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
				*/

				return $action;
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
		// dd($request->all());
		if ($request->advance_received > 0) {
			//dd($request->id);
			$get_previous_trips = Trip::select('id')
				->where('employee_id', Auth::user()->entity_id)
				->where('id', '!=', $request->id)
				// ->whereIn('advance_request_approval_status_id', [3260, 3261])
				->where('advance_received','>', 0)
				->whereNotIn('status_id', [3026, 3032, 3039, 3038])
				->orderBy('id', 'DESC')->first();
			//dd($get_previous_trips);

			if ($get_previous_trips) {
				return response()->json(['success' => false, 'errors' => ['Advance Amount Eligible, After All Previous Claim Process Completed']]);
			}
			$get_previous_entry = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')->where('ey_employee_claims.employee_id', Auth::user()->entity_id)->where('ey_employee_claims.status_id', 3031)->orderBy('ey_employee_claims.id', 'DESC')->select('ey_employee_claims.balance_amount')->first();
			if ($get_previous_entry) {
				$previous_amount = $get_previous_entry->balance_amount;
				if ($request->advance_received > $previous_amount) {
					return response()->json(['success' => false, 'errors' => ['Your Previous Trip Claim Amount is Pending.Pay previous trip balance Amount']]);
				} else {

				}
			}
		}
		if ($request->id) {
			$trip_start_date_data = Trip::where('employee_id', Auth::user()->entity_id)
				->where('id', '!=', $request->id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
		} else {
			//IF EMPLOYEE DLOB OR DLOB PV THEN NOT ALLOW TO CREATE TRIP REQUEST.
			if(Auth::user()->business_id == 1 || Auth::user()->business_id == 9){
				return response()->json([
					'success'=> false,
					'errors' => ['Kindly use valid user login to create trip request!']
				]);
			}

			$trip_start_date_data = Trip::where('employee_id', Auth::user()->entity_id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();

			//Total Trips Pending
			$total_trips = Trip::where('employee_id', Auth::user()->entity_id)->where('status_id', 3021)->count();
			if ($total_trips >= 5) {
				return response()->json(['success' => false, 'errors' => "Your previous trips waiting for approval!"]);
			}
		}
		if ($trip_start_date_data && $trip_start_date_data->status_id != 3032) {
			return response()->json(['success' => false, 'errors' => "You have another trip on this trip period"]);
		}
		$date_lessthan_previous_trip = Trip::select('id')->where('employee_id', Auth::user()->entity_id)
			->where('id', '!=', $request->id)
			->where('end_date', '>=', date("Y-m-d", strtotime($request->start_date)))
			->where('status_id', '!=', 3032)
			->first();
		if ($date_lessthan_previous_trip) {
			// return response()->json(['success' => false, 'errors' => "Trip date should be Greater than your previous trip"]);
		}

		$size = sizeof($request->visits);
		for ($i = 0; $i < $size; $i++) {
			/*if (!(($request->visits[$i]['date'] >= $request->start_date) && ($request->visits[$i]['date'] <= $request->end_date))) {
				return response()->json(['success' => false, 'errors' => "Departure date should be within Trip Period"]);*/
			if (!((date("Y-m-d", strtotime($request->visits[$i]['date'])) >= date("Y-m-d", strtotime($request->start_date))) && (date("Y-m-d", strtotime($request->visits[$i]['date'])) <= date("Y-m-d", strtotime($request->end_date))))) {
				return response()->json(['success' => false, 'errors' => ["Departure date should be within Trip Period"]]);

			}

			$next_key = $i + 1;
			if (!($next_key >= $size)) {
				/*if ($request->visits[$next_key]['date'] < $request->visits[$i]['date']) {
					return response()->json(['success' => false, 'errors' => "Return Date Should Be Greater Than Or Equal To Departure Date"]);*/
				if (date("Y-m-d", strtotime($request->visits[$next_key]['date'])) < date("Y-m-d", strtotime($request->visits[$i]['date']))) {
					return response()->json(['success' => false, 'errors' => ["Return Date Should Be Greater Than Or Equal To Departure Date"]]);
				}
			}

		}
		return Trip::saveTrip($request);
	}

	public function viewTrip($trip_id) {
		return Trip::getViewData($trip_id);
	}

	public function eyatraTripFilterData() {
		return Trip::getFilterData($type = 1);
	}

	public function deleteTrip($trip_id) {
		return Trip::deleteTrip($trip_id);
	}

	public function cancelTrip(Request $r) {
		return Trip::cancelTrip($r);
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

	public function cancelTripVisit($visit_id) {
		return Trip::cancelTripVisit($visit_id);
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
			'agent.user',
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
$visit_booking= VisitBooking::select('is_proof_attached')->where('visit_id',$visit_id)->get()->first();
		if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062 || $visit_booking->is_proof_attached == 1) {
			$relations[] = 'bookings';
			$relations[] = 'bookings.attachments';
			$relations[] = 'bookings.type';
			$relations[] = 'bookings.travelMode';
			$relations[] = 'bookings.paymentStatus';
		}

		$visit = Visit::with($relations)
			->find($visit_id);

		$this->data['visit'] = $visit;
		$this->data['trip'] = $visit->trip;
		if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062 || $visit_booking->is_proof_attached == 1) {
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
