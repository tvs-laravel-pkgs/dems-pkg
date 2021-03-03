<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\Trip;
use Validator;
use Yajra\Datatables\Datatables;

class TripClaimVerificationThreeController extends Controller {
	public function listEYatraTripClaimVerificationThreeList(Request $r) {
		$trips = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
		// ->join('outlets', 'outlets.id', 'e.outlet_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'ey_employee_claims.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(trips.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('DATE_FORMAT(trips.end_date,"%d-%m-%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
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
					$query->where("status.id", $r->get('status_id'));
				}else{
					$query->whereIn("status.id", [3034, 3030]);
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->from_date)) {
					$query->where('trips.start_date', date('Y-m-d', strtotime($r->from_date)));
				}
			})
			->where(function ($query) use ($r) {
				if (!empty($r->to_date)) {
					$query->where('trips.end_date', date('Y-m-d', strtotime($r->to_date)));
				}
			})
		// ->whereIn('ey_employee_claims.status_id', [3031, 3025, 3030]) //PAYMENT PENDING FOR EMPLOYEE & PAYMENT PENDING FOR FINANCIER & FINANCIER PAYMENT HOLD
			// ->whereIn('ey_employee_claims.status_id', [3034, 3030]) //PAYMENT PENDING & FINANCIER PAYMENT HOLD
		// ->where('outlets.cashier_id', Auth::user()->entity_id) //FINANCIER
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc');

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');

				return '
				<a href="#!/trip/claim/verification3/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function viewEYatraTripClaimVerificationThree($trip_id) {
		return Trip::getClaimViewData($trip_id);
	}

	public function approveFinancierTripClaimVerification($trip_id) {

		// dd($trip_id);
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$employee_claim = EmployeeClaim::where('trip_id', $trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim->status_id = 3031; //Payment pending for Employee
		$employee_claim->save();

		$trip->status_id = 3031; //Payment pending for Employee
		$trip->save();
		//Approval Log
		$approval_log = ApprovalLog::saveApprovalLog(3581, $trip->id, 3603, Auth::user()->entity_id, Carbon::now());
		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 6, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Payment Pending');

		return response()->json(['success' => true]);
	}
	public function approveTripClaimVerificationThree(Request $r) {
		// dd($r->all());
		try {
			DB::beginTransaction();
			$error_messages = [
				'reference_number.unique' => "Reference Number is already taken",
			];

			$validator = Validator::make($r->all(), [
				'reference_number' => [
					'required:true',
					'unique:payments,reference_number',

				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			$trip = Trip::find($r->trip_id);
			if (!$trip) {
				return response()->json(['success' => false, 'errors' => ['Trip not found']]);
			}

			$employee_claim = EmployeeClaim::where('trip_id', $r->trip_id)->first();
			if (!$employee_claim) {
				return response()->json(['success' => false, 'errors' => ['Trip not found']]);
			}
			$employee_claim->status_id = 3026; //PAID
			$employee_claim->save();

			$trip->status_id = 3026; //PAID
			$trip->save();

			//PAYMENT SAVE
			$payment = Payment::firstOrNew(['payment_of_id' => 3251,'entity_id' => $trip->id]);
			if ($payment->exists) {
				$payment->updated_by = Auth::user()->id;
				$payment->updated_at = Carbon::now();
			} else {
				$payment->created_by = Auth::user()->id;
				$payment->created_at = Carbon::now();
			}
			$payment->fill($r->all());
			$payment->date = date('Y-m-d', strtotime($r->date));
			$payment->payment_of_id = 3251;
			$payment->entity_id = $trip->id;
			// $payment->created_by = Auth::user()->id;
			$payment->save();

			$employee_claim->payment_id = $payment->id;
			$employee_claim->claim_approval_datetime = date('Y-m-d H:i:s');
			$employee_claim->save();

			$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
			//Approval Log
			$approval_log = ApprovalLog::saveApprovalLog(3581, $r->trip_id, 3604, Auth::user()->entity_id, Carbon::now());
			$notification = sendnotification($type = 9, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Paid');

			DB::commit();
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function rejectTripClaimVerificationThree(Request $r) {

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

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 7, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Claim Rejected');

		return response()->json(['success' => true]);
	}

	public function holdTripClaimVerificationThree(Request $r) {
		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim = EmployeeClaim::where('trip_id', $r->trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim->status_id = 3030; //Financier Payment Hold
		$employee_claim->save();

		$trip->status_id = 3030; //Financier Payment Hold
		$trip->save();

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 8, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Claim Hold');

		return response()->json(['success' => true]);
	}

}
