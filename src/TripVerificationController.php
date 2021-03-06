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
				<a href="#!/trip/verification/form/' . $trip->id . '">
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
			'visits.agent.user',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'employee.user',
			'employee.designation',
			'employee.grade',
			'employee.manager',
			'employee.manager.user',
			'purpose',
			'status',
		])
			->find($trip_id);
			//dd($trip);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		if (!Entrust::can('verify-all-trips') && $trip->manager_id != Auth::user()->entity_id) {
			return response()->json(['success' => false, 'errors' => ['You are not authorized to view this trip']]);
		}


		$start_date = $trip->visits()->select(DB::raw('MIN(visits.departure_date) as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('MAX(visits.departure_date) as end_date'))->first();
		$days = Trip::select(DB::raw('DATEDIFF(end_date,start_date)+1 as days'))->where('id', $trip_id)->first();
		$trip->days = $days->days;
		$this->data['trip'] = $trip;
		$this->data['success'] = true;
		$this->data['trip_reject_reasons'] = $trip_reject_reasons = Entity::trip_request_rejection();

		if ($trip->advance_request_approval_status_id) {
			if ($trip->advance_request_approval_status_id == 3260 || $trip->advance_request_approval_status_id == 3262) {
				$trip_reject = 1;
			} else {
				$trip_reject = 0;
			}
		} else {
			$trip_reject = 1;
		}

		$this->data['trip_reject'] = $trip_reject;

		return response()->json($this->data);
	}

	// public function saveTripVerification(Request $r) {
	// 	return Trip::saveTripVerification($r);
	// }

	public function eyatraTripVerificationFilterData() {

		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		$this->data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 501)->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
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
