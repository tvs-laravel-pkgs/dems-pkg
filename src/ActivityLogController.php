<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\ActivityLog;
use Yajra\Datatables\Datatables;

class ActivityLogController extends Controller {
	public function listEYatraActivityLogList(Request $r) {

		$activity_logs_list = ActivityLog::leftJoin('users', 'activity_logs.user_id', 'users.id')
			->leftJoin('configs as entity_types', 'activity_logs.entity_type_id', 'entity_types.id')
			->leftJoin('configs as activities', 'activity_logs.activity_id', 'activities.id')
			->select(
				'entity_types.name as entity_name',
				'activities.name as activity_name',
				DB::raw('DATE_FORMAT(activity_logs.date_time,"%d/%m/%Y") as date'))
			->where('activity_logs.user_id', Auth::user()->id);
		// ->get();

		return Datatables::of($activity_logs_list)
			->addColumn('action', function ($activity_logs_list) {
			->make(true);
	}
}
