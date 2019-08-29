<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\Trip;
use Yajra\Datatables\Datatables;

class TripVerificationController extends Controller {
	public function listTripVerification(Request $r) {
		$trips = Trip::getVerficationPendingList($r);
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/trip/verification/form/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
	}

	public function tripVerificationFormData($trip_id) {

		$trip = Trip::with([
			'visits' => function ($q) {
				$q->orderBy('visits.id', 'asc');
			},
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'employee.user',
			'employee.designation',
			'purpose',
			'status',
		])
			->find($trip_id);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		if (!Entrust::can('verify-all-trips') && $trip->manager_id != Auth::user()->entity_id) {
			return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		$days = $trip->visits()->select(DB::raw('DATEDIFF(MAX(visits.departure_date),MIN(visits.departure_date)) as days'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $end_date->end_date;
		$trip->days = $days->days + 1;
		$this->data['trip'] = $trip;
		$this->data['success'] = true;
		$this->data['trip_reject_reasons'] = $trip_reject_reasons = Entity::trip_request_rejection();
		return response()->json($this->data);
	}

	// public function saveTripVerification(Request $r) {
	// 	return Trip::saveTripVerification($r);
	// }

	public function eyatraTripVerificationFilterData() {
		$this->data['employee_list'] = Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3122)
			->where('company_id', Auth::user()->company_id)->get();
		$this->data['purpose_list'] = Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get();
		$this->data['trip_status_list'] = Config::select('name', 'id')->where('config_type_id', 501)->get();
		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function approveTrip(Request $r) {
		return Trip::approveTrip($r);
	}

	public function rejectTrip(Request $r) {
		return Trip::rejectTrip($r);

	}

}
