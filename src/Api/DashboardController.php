<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Uitoux\EYatra\MobileNotificationDetail;
use Uitoux\EYatra\Trip;
use Illuminate\Http\Request;

class DashboardController extends Controller {
	public $successStatus = 200;

	public function saveNotification($id) {
		if ($id == 'All') {
			$notification_details = MobileNotificationDetail::where('user_id', Auth::user()->id)->forceDelete();
		} else {
			$notification_details = MobileNotificationDetail::where('id', $id)->forceDelete();
		}
		return response()->json(['success' => true]);
	}
	public function getNotification() {
		$notification_details = MobileNotificationDetail::where('user_id', Auth::user()->id)->get();
		return response()->json(['success' => true, 'notification_details' => $notification_details]);
	}
	public function getDashboard() {
		$total_trips = Trip::where('status_id', '!=', '3032')->where('trips.status_id', '!=', '3022')->where('employee_id', Auth::user()->entity_id)
			->count();

		$total_claims_pending = Trip::where('employee_id', Auth::user()->entity_id)->whereIN('trips.status_id', [3023, 3024, 3025, 3029, 3030, 3034, 3036])->count();

		$total_upcoming_trips = Trip::where('employee_id', Auth::user()->entity_id)->where('status_id', 3028)->where('start_date', '>', date('Y-m-d'))->count();
		$trips_pending=Trip::where('status_id', '=', '3021')->where('employee_id', Auth::user()->entity_id)
			->count();
		$trips_approved=Trip::where('status_id', '=', '3028')->where('employee_id', Auth::user()->entity_id)
			->count();
		$trips_rejected=Trip::where('status_id', '=', '3022')->where('employee_id', Auth::user()->entity_id)
			->count();
		$trips_completed=Trip::where('status_id', '=', '3026')->where('employee_id', Auth::user()->entity_id)->count();
		$claims_new=Trip::where('status_id', '=', [3033,3028])->where('employee_id', Auth::user()->entity_id)
			->count();
		$claims_requested=Trip::where('status_id', '=', '3023')->where('employee_id', Auth::user()->entity_id)
			->count();
		$claims_approved=Trip::where('status_id', '=', '3029')->where('employee_id', Auth::user()->entity_id)
			->count();
		$claims_rejected=Trip::where('status_id', '=', '3024')->where('employee_id', Auth::user()->entity_id)
			->count();
		$claims_completed=Trip::where('status_id', '=', '3026')->where('employee_id', Auth::user()->entity_id)->count();
		/*$verification_new=Trip::where('status_id', '=', [3033,3028])->where('employee_id', Auth::user()->entity_id)
			->count();*/
		
		$verification_trips=Trip::where('status_id','=',3021)->where('manager_id', Auth::user()->entity_id)->count();
		$verification_requested=Trip::where('status_id', '=', '3023')->where('manager_id', Auth::user()->entity_id)
			->count();
		$verification_approved=Trip::where('status_id', '=', '3029')->where('manager_id', Auth::user()->entity_id)
			->count();
		$verification_rejected=Trip::where('status_id', '=', '3024')->where('manager_id', Auth::user()->entity_id)
			->count();
		$verification_claim2_requested=Trip::where('status_id', '=', '3029')->where('manager_id', Auth::user()->entity_id)
			->count();
		$verification_claim2_approved=Trip::where('status_id', '=', '3026')->where('manager_id', Auth::user()->entity_id)
			->count();
		$verification_claim2_rejected=Trip::where('status_id', '=', '3024')->where('manager_id', Auth::user()->entity_id)
			->count();
		$verification_completed=Trip::where('status_id', '=', '3026')->where('manager_id', Auth::user()->entity_id)->count();

		$dashboard_details['total_trips'] = $total_trips;
		$dashboard_details['total_claims_pending'] = $total_claims_pending;
		$dashboard_details['total_upcoming_trips'] = $total_upcoming_trips;

        $dashboard_details['trips_pending'] = $trips_pending;
		$dashboard_details['trips_approved'] = $trips_approved;
		$dashboard_details['trips_rejected'] = $trips_rejected;
		$dashboard_details['trips_completed'] = $trips_completed;
		$dashboard_details['claims_new'] = $claims_new;
		$dashboard_details['claims_requested'] = $claims_requested;
		$dashboard_details['claims_approved'] = $claims_approved;
		$dashboard_details['claims_rejected'] = $claims_rejected;
		$dashboard_details['claims_completed'] = $claims_completed;
		$dashboard_details['verification_requested'] = $verification_requested;
		$dashboard_details['verification_approved'] = $verification_approved;
		$dashboard_details['verification_rejected'] = $verification_rejected;
		$dashboard_details['verification_claim2_requested'] = $verification_claim2_requested;
		$dashboard_details['verification_claim2_approved'] = $verification_claim2_approved;
		$dashboard_details['verification_claim2_rejected'] = $verification_claim2_rejected;
		$dashboard_details['verification_completed'] = $verification_completed;

		$getversion_code = DB::table('version_control')->where('project_name', 'dems')->orderBy('id', 'DESC')->first();
		if ($getversion_code != NULL) {
			$version_code = $getversion_code->version_code;
			$version_name = $getversion_code->version_name;
		} else {
			$version_code = 0;
			$version_name = 0;
		}

		$dashboard_details['version_code'] = $version_code;
		$dashboard_details['version_name'] = $version_name;

		//Get User Information
		$user = User::with([
			'employee_details',
			'employee_details.reportingTo',
			'employee_details.reportingTo.user',
			'employee_details.paymentMode',
			'employee_details.grade',
			'employee_details.designation',
			'employee_details.bankDetail',
			'employee_details.chequeDetail',
			'employee_details.walletDetail',
			'employee_details.walletDetail.type',
			'employee_details.sbu',
			'employee_details.sbu.lob',
			'roles',
		])
			->find(Auth::user()->id);
		$user->permissions = $user->permissions($only_mobile = true);
		$user->entity->designation;
		$user->entity->grade;
		$user->entity->outlet->address;
		$user->employee = $user->entity;

		return response()->json(['success' => true, 'dashboard_details' => $dashboard_details, 'user' => $user]);

	}

}
