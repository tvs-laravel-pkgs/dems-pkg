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

				/*$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');

				if ($agent_claim_list->claim_status == '3024') {
					return '
						<a href="#!/eyatra/agent/claim/edit/' . $agent_claim_list->id . '">
							<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
						</a>
						<a href="#!/eyatra/agent/claim/view/' . $agent_claim_list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
				} else {
					return '
						<a href="#!/eyatra/agent/claim/view/' . $agent_claim_list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>';
				}

			})*/
			->make(true);
	}
}
