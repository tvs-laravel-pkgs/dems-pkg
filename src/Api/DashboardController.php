<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Uitoux\EYatra\MobileNotificationDetail;
use Uitoux\EYatra\Trip;

class DashboardController extends Controller {
	public $successStatus = 200;

	public function saveNotification($id) {
		$notification_details = MobileNotificationDetail::where('id', $id)->forceDelete();
		return response()->json(['success' => true]);
	}
	public function getNotification() {
		$notification_details = MobileNotificationDetail::where('user_id', Auth::user()->id)->get();
		return response()->json(['success' => true, 'notification_details' => $notification_details]);
	}
	public function getDashboard() {
		$total_trips = Trip::where('status_id', '!=', '3032')->where('employee_id', Auth::user()->entity_id)
			->count();

		$total_claims_pending = Trip::where('employee_id', Auth::user()->entity_id)->where('status_id', '!=', '3032')->where('status_id', '!=', '3026')->count();

		$total_upcoming_trips = Trip::where('employee_id', Auth::user()->entity_id)->where('status_id', '!=', '3032')->where('start_date', '>', date('Y-m-d'))->count();

		$dashboard_details['total_trips'] = $total_trips;
		$dashboard_details['total_claims_pending'] = $total_claims_pending;
		$dashboard_details['total_upcoming_trips'] = $total_upcoming_trips;

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
