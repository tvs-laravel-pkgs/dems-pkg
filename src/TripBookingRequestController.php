<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class TripBookingRequestController extends Controller {

	public function filterEYatraTripBookingRequests() {
		$this->data['employee_list'] = $employee_list = Employee::select(DB::raw('concat(employees.code, "-" ,users.name) as name,employees.id'))
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)->get();
		$this->data['status_list'] = $status_list = Config::select('name', 'id')->where('config_type_id', 512)->get();
		$this->data['booking_status_list'] = $booking_status_list = Config::select('name', 'id')->where('config_type_id', 503)->get();

		return response()->json($this->data);
	}

	public function listTripBookingRequests(Request $r) {
		if (!empty($r->employee)) {
			$employee = $r->employee;
		} else {
			$employee = null;
		}
		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;
		}
		if (!empty($r->booking_status)) {
			$booking_status = $r->booking_status;
		} else {
			$booking_status = null;
		}

		$visits = Trip::select([
			'trips.id as trip_id',
			'trips.number as trip_number',
			'e.code as ecode',
			'users.name as ename',
			'status.name as status',
			'trips.status_id',
			DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y") as created_on'),
			DB::raw('COUNT(v.id) as tickets_count'),
		])
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('configs as bs', 'bs.id', 'v.booking_status_id')
			->join('configs as status', 'status.id', 'v.status_id')
			->join('users as createdBy', 'createdBy.id', 'trips.created_by')
			->leftjoin('agents as a', 'a.id', 'v.agent_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->where('users.user_type_id', 3121)
			->where('createdBy.company_id', Auth::user()->company_id)
			->where(function ($query) use ($r, $employee) {
				if (!empty($employee)) {
					$query->where('e.id', $employee);
				}
			})
			->where(function ($query) use ($r, $status) {
				if (!empty($status)) {
					$query->where('status.id', $status);
				}
			})
			->where(function ($query) use ($r, $booking_status) {
				if (!empty($booking_status)) {
					$query->where('bs.id', $booking_status);
				}
			})
			->groupBy('v.trip_id')
			->orderBy('trips.created_at', 'desc')
		;

		// $visits = Visit::from('visits as v')
		// 	->join('trips as t', 'v.trip_id', 't.id')
		// 	->join('employees as e', 'e.id', 't.employee_id')
		// 	->join('ncities as fc', 'fc.id', 'v.from_city_id')
		// 	->join('ncities as tc', 'tc.id', 'v.to_city_id')
		// 	->join('entities as tm', 'tm.id', 'v.travel_mode_id')
		// 	->join('configs as bs', 'bs.id', 'v.booking_status_id')
		// 	->join('agents as a', 'a.id', 'v.agent_id')
		// 	->join('configs as status', 'status.id', 'v.status_id')
		// 	->select(
		// 		'v.id',
		// 		't.number',
		// 		'e.code as ecode',
		// 		DB::raw('DATE_FORMAT(v.date,"%d/%m/%Y") as date'),
		// 		'fc.name as from',
		// 		'tc.name as to',
		// 		'tm.name as travel_mode',
		// 		'bs.name as booking_status',
		// 		'a.name as agent',
		// 		'status.name as status'

		// 	)
		// 	->orderBy('t.id', 'desc')
		// 	->orderBy('t.created_at', 'desc')
		// 	->orderBy('v.status_id', 'desc')
		// 	->groupBy('v.trip_id')
		// // ->get()
		// ;

		if (!Entrust::can('view-all-trip-booking-requests')) {
			$visits->where('v.agent_id', Auth::user()->entity_id);
		}

		return Datatables::of($visits)
			->addColumn('booking_status', function ($visit) {
				$bookings = Visit::where('trip_id', $visit->trip_id)
					->where('booking_status_id', 3060)
					->count();
				$ticketBooked = Visit::where('trip_id', $visit->trip_id)
					->where('booking_status_id', 3065)
					->count();
				// if ($bookings) {
				// 	return "Pending";
				// } else {
				// 	return "Booked";
				// }
				// dd($bookings);
				return $bookings ? "Pending" : ($ticketBooked ? "Ticket Uploaded" : "Booked");
			})
			->addColumn('trip_status', function ($visit) {
				if ($visit->status_id == '3032') {
					return "Trip Cancelled";
				} elseif ($visit->status_id == '3022') {
					return "Trip Rejected";
				} else {
					return "-";
				}
			})
			->addColumn('action', function ($visit) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				// $fileUploadImg = asset('public/img/content/yatra/file-bg.svg');

				$action = '<a href="#!/trips/booking-requests/view/' . $visit->trip_id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
				// $action .= '<a href="javascript:;" onclick="angular.element(this).scope().uploadProofDocument(' . $visit->trip_id . ')" data-toggle="modal" data-target="#uploadProofDocumentModal" title="Proof Document">
				//             	<img src="' . $fileUploadImg . '" alt="Upload Proof Document" class="img-responsive">
				//             </a>';
				return $action;

			})
			->make(true);
	}

	public function tripBookingRequestsViewData($visit_id) {

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
			'other_charges',
			'managerVerificationStatus',
			'trip.employee',
			'trip.purpose',
			'trip.status',
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

		if (!Entrust::can('view-all-trip-booking-requests') && $visit->agent_id != Auth::user()->entity_id) {
			return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
		}

		$agent = Agent::find(Auth::user()->entity_id);
		$this->data['travel_mode'] = $agent_travel_mode = $agent->travelModes;
		$this->data['visit'] = $visit;
		$this->data['trip'] = $visit->trip;
		if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
			$this->data['bookings'] = $visit->bookings;
		}
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function listTripTatkalBookingRequests(Request $r) {

		if (!empty($r->employee)) {
			$employee = $r->employee;
		} else {
			$employee = null;
		}
		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;
		}

		$visits = Trip::join('employees as e', 'e.id', 'trips.employee_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('configs as bs', 'bs.id', 'v.booking_status_id')
			->join('configs as status', 'status.id', 'v.status_id')
			->join('users as cb', 'cb.id', 'trips.created_by')
			->leftjoin('agents as a', 'a.id', 'v.agent_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->where('users.user_type_id', 3121)
			->where('v.booking_status_id', 3063)
			->select('trips.id as trip_id',
				'trips.number as trip_number',
				'e.code as ecode', 'users.name as ename',
				'status.name as status',
				DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y") as created_on'),
				DB::raw('COUNT(v.id) as tickets_count')

			)
			->where(function ($query) use ($r, $employee) {
				if (!empty($employee)) {
					$query->where('e.id', $employee);
				}
			})

			->where(function ($query) use ($r, $status) {
				if (!empty($status)) {
					$query->where('status.id', $status);
				}
			})
			->groupBy('v.trip_id')
			->orderBy('trips.created_at', 'desc')
			->where('cb.company_id', Auth::user()->company_id)
		// ->get()
		;
		if (!Entrust::can('view-all-trip-booking-requests')) {
			$visits->where('v.agent_id', Auth::user()->entity_id);
		}

		return Datatables::of($visits)
			->addColumn('booking_status', function ($visit) {
				$bookings = Visit::where('trip_id', $visit->trip_id)
					->where('booking_status_id', 3060)
					->count();
				return $bookings ? "Pending" : "Booked";
			})
			->addColumn('action', function ($visit) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/trips/tatkal/booking-requests/view/' . $visit->trip_id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
	}
}
