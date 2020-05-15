<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Session;
use Uitoux\EYatra\ActivityLog;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Trip;
use Yajra\Datatables\Datatables;

class TripClaimVerificationController extends Controller {
	public function eyatraVerificationFilterData() {

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('verifier_employee_id') ? intval(session('verifier_employee_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('verifier_outlet_id') ? intval(session('verifier_outlet_id')) : '-1';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('verifier_purpose_id') ? intval(session('verifier_purpose_id')) : '-1';

		$start_date = session('verifier_start_date');
		$end_date = session('verifier_end_date');
		if (!$start_date) {
			$start_date = date('01-m-Y');
			$end_date = date('t-m-Y');
		}

		$this->data['start_date'] = $start_date;
		$this->data['end_date'] = $end_date;

		$this->data['success'] = true;
		// dd($this->data);
		return response()->json($this->data);
	}
	public function eyatraOutstationClaimVerificationGetData(Request $r) {

		// dd($r->all());
		if (!empty($r->from_date) && $r->from_date != '<%$ctrl.start_date%>') {
			$from_date = date('Y-m-d', strtotime($r->from_date));
			Session::put('verifier_start_date', $r->from_date);
			Session::put('verifier_end_date', $r->to_date);
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date) && $r->to_date != '<%$ctrl.end_date%>') {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}

		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['verifier_employee_id' => $r->employee_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['verifier_outlet_id' => $r->outlet_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['verifier_purpose_id' => $r->purpose_id]);
		}

		$trips = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('outlets', 'outlets.id', 'e.outlet_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('DATE_FORMAT(trips.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('DATE_FORMAT(trips.end_date,"%d-%m-%Y") as end_date'),
				'purpose.name as purpose',
				'outlets.name as outlet_name',
				'status.name as status'
			)

			->where('e.company_id', Auth::user()->company_id)
			->where(function ($query) use ($r) {
				if ($r->get('employee_id') && $r->get('employee_id') != '<%$ctrl.filter_employee_id%>') {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('outlet_id') && $r->get('outlet_id') != '<%$ctrl.filter_outlet_id%>') {
					$query->where("outlets.id", $r->get('outlet_id'))->orWhere(DB::raw("-1"), $r->get('outlet_id'));
				}
			})

			->where(function ($query) use ($r) {
				if ($r->get('purpose_id') && $r->get('purpose_id') != '<%$ctrl.filter_purpose_id%>') {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})

			->where(function ($query) use ($from_date) {
				if (!empty($from_date)) {
					//dd('in');
					$query->where('trips.start_date', '>=', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('trips.end_date', '<=', $to_date);
				}
			})

			->where('ey_employee_claims.status_id', 3036) //CLAIM REQUESTED
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc');

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');

				return '
				<a href="#!/outstation-trip/claim/verification/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function approveOutstationTripClaimVerification($trip_id) {
		// dd($trip_id);
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim = EmployeeClaim::where('trip_id', $trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$employee_claim->status_id = 3034; //Payment Pending
		$trip->status_id = 3034; //Payment Pending

		$employee_claim->save();
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Employee Claims Verifier Approved";
		$activity['activity'] = "approve";
		$activity_log = ActivityLog::saveLog($activity);
		//Approval Log
		$approval_log = ApprovalLog::saveApprovalLog(3581, $trip->id, 3622, Auth::user()->entity_id, Carbon::now());
		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 6, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Claim Approved');

		return response()->json(['success' => true]);
	}

	public function rejectOutstationTripClaimVerification(Request $r) {
		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$employee_claim = EmployeeClaim::where('trip_id', $r->trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim->status_id = 3024; //Claim Rejected
		$employee_claim->save();

		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3024; //Claim Rejected
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Employee Claims Verifier Rejected";
		$activity['activity'] = "reject";
		$activity_log = ActivityLog::saveLog($activity);

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 7, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Claim Rejected');

		return response()->json(['success' => true]);
	}

	public function eyatraLocalClaimVerificationGetData(Request $r) {

		// dd($r->all());
		if (!empty($r->from_date) && $r->from_date != '<%$ctrl.start_date%>') {
			$from_date = date('Y-m-d', strtotime($r->from_date));
			Session::put('verifier_start_date', $r->from_date);
			Session::put('verifier_end_date', $r->to_date);
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date) && $r->to_date != '<%$ctrl.end_date%>') {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}

		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['verifier_employee_id' => $r->employee_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['verifier_outlet_id' => $r->outlet_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['verifier_purpose_id' => $r->purpose_id]);
		}

		$trips = LocalTrip::join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('outlets', 'outlets.id', 'e.outlet_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('DATE_FORMAT(local_trips.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('DATE_FORMAT(local_trips.end_date,"%d-%m-%Y") as end_date'),
				'purpose.name as purpose',
				'outlets.name as outlet_name',
				'status.name as status'
			)

			->where('e.company_id', Auth::user()->company_id)
			->where(function ($query) use ($r) {
				if ($r->get('employee_id') && $r->get('employee_id') != '<%$ctrl.filter_employee_id%>') {
					$query->where("e.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('outlet_id') && $r->get('outlet_id') != '<%$ctrl.filter_outlet_id%>') {
					$query->where("outlets.id", $r->get('outlet_id'))->orWhere(DB::raw("-1"), $r->get('outlet_id'));
				}
			})

			->where(function ($query) use ($r) {
				if ($r->get('purpose_id') && $r->get('purpose_id') != '<%$ctrl.filter_purpose_id%>') {
					$query->where("purpose.id", $r->get('purpose_id'))->orWhere(DB::raw("-1"), $r->get('purpose_id'));
				}
			})

			->where(function ($query) use ($from_date) {
				if (!empty($from_date)) {
					$query->where('local_trips.start_date', '>=', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('local_trips.end_date', '<=', $to_date);
				}
			})

			->where('local_trips.status_id', 3036) //CLAIM REQUESTED
			->groupBy('local_trips.id')
			->orderBy('local_trips.created_at', 'desc');

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');

				return '
				<a href="#!/local-trip/claim/verification/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function approveLocalTripClaimVerification($trip_id) {
		// dd($trip_id);
		$trip = LocalTrip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$trip->status_id = 3034; //Payment Pending

		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Employee Claims Verifier Approved";
		$activity['activity'] = "approve";
		$activity_log = ActivityLog::saveLog($activity);
		//Approval Log
		$approval_log = ApprovalLog::saveApprovalLog(3582, $trip->id, 3623, Auth::user()->entity_id, Carbon::now());
		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();

		$notification = sendnotification($type = 6, $trip, $user, $trip_type = "Local Trip", $notification_type = 'Claim Approved');

		return response()->json(['success' => true]);
	}

	public function rejectLocalTripClaimVerification(Request $r) {
		// dd($r->all());
		$trip = LocalTrip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3024; //Claim Rejected
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Employee Claims Verifier Rejected";
		$activity['activity'] = "reject";
		$activity_log = ActivityLog::saveLog($activity);

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 7, $trip, $user, $trip_type = "Local Trip", $notification_type = 'Claim Rejected');

		return response()->json(['success' => true]);
	}

}
