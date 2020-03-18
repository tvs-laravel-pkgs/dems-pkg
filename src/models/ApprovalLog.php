<?php

namespace Uitoux\EYatra;
use App\User;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Uitoux\EYatra\ApprovalLog;

class ApprovalLog extends Model {
	protected $table = 'approval_logs';
	public $timestamps = false;
	protected $fillable = [
		'type_id',
		'entity_id',
		'approval_type_id',
		'approved_by_id',
		'approved_at',
	];

	public static function saveApprovalLog($type_id, $entity_id, $approval_type_id, $approved_by_id, $approved_at) {
		$approvalLog = new self();
		$approvalLog->type_id = $type_id;
		$approvalLog->entity_id = $entity_id;
		$approvalLog->approval_type_id = $approval_type_id;
		$approvalLog->approved_by_id = $approved_by_id;
		$approvalLog->approved_at = $approved_at;
		$approvalLog->save();

	}
	public static function getOutstationList($r) {
		if (!empty($r->from_date)) {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date)) {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}
		$lists = ApprovalLog::join('trips', 'trips.id', 'approval_logs.entity_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->leftJoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'approval_logs.entity_id')
			->select(
				'approval_logs.id as id',
				'approval_logs.entity_id as entity_id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('DATE_FORMAT(trips.start_date,"%d-%m-%Y") as start_date'),
				// 'trips.start_date',
				DB::raw('DATE_FORMAT(trips.end_date,"%d-%m-%Y") as end_date'),
				// 'trips.end_date',
				'trips.status_id',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'purpose.name as purpose',
				'status.name as status', 'status.name as status_name',
				DB::raw('DATE_FORMAT(approval_logs.approved_at,"%d-%m-%Y %h:%i:%s %p") as date'),
				'approval_logs.approval_type_id as type_id'
			)
			->where('users.user_type_id', 3121)
			->where('approval_logs.type_id', 3581)
			->where('approval_logs.approved_by_id', Auth::user()->entity_id)
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
			->orderBy('trips.status_id', 'desc')
			->where(function ($query) use ($r) {
				if ($r->get('type_id')) {
					$query->where("approval_logs.approval_type_id", $r->get('type_id'))->orWhere(DB::raw("-1"), $r->get('type_id'));
				} else {
					$query->where("approval_logs.approval_type_id", 3600)->orWhere(DB::raw("-1"), $r->get('type_id'));
				}
			})
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
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})

			->where(function ($query) use ($from_date) {
				if (!empty($from_date)) {
					$query->where('trips.start_date', '>=', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('trips.end_date', '<=', $to_date);
				}
			})
		;
		return $lists;
	}

public static function getTripAdvanceList($r) {
		if (!empty($r->from_date)) {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date)) {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}
		$lists = ApprovalLog::join('trips', 'trips.id', 'approval_logs.entity_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->leftJoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'approval_logs.entity_id')
			->select(
				'approval_logs.id as id',
				'approval_logs.entity_id as entity_id',
				'trips.number',
				'trips.advance_received as advance_amount',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('DATE_FORMAT(trips.start_date,"%d-%m-%Y") as start_date'),
				// 'trips.start_date',
				DB::raw('DATE_FORMAT(trips.end_date,"%d-%m-%Y") as end_date'),
				// 'trips.end_date',
				'trips.status_id',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'purpose.name as purpose',
				'status.name as status', 'status.name as status_name',
				DB::raw('DATE_FORMAT(approval_logs.approved_at,"%d-%m-%Y %h:%i:%s %p") as date'),
				'approval_logs.approval_type_id as type_id'
			)
			->where('users.user_type_id', 3121)
			->where('approval_logs.type_id', 3581)
			->where('approval_logs.approval_type_id', 3620)
			->where('approval_logs.approved_by_id', Auth::user()->entity_id)
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
			->orderBy('trips.status_id', 'desc')
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
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
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
		;
		return $lists;
	}


	public static function getLocalTripList($r) {
		if (!empty($r->from_date)) {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date)) {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}
		$lists = ApprovalLog::join('local_trips', 'local_trips.id', 'approval_logs.entity_id')
			->leftJoin('local_trip_visit_details', 'local_trip_visit_details.trip_id', 'local_trips.id')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->select(
				'approval_logs.id as id',
				'approval_logs.entity_id as entity_id',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('DATE_FORMAT(local_trips.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('DATE_FORMAT(local_trips.end_date,"%d-%m-%Y") as end_date'),
				'purpose.name as purpose', 'status.name as status_name',
				DB::raw('DATE_FORMAT(approval_logs.approved_at,"%d-%m-%Y %h:%i:%s %p") as date'),
				'approval_logs.approval_type_id as type_id'
			)
			->where('users.user_type_id', 3121)
			->where('approval_logs.type_id', 3582)
			->where('approval_logs.approved_by_id', Auth::user()->entity_id)
			->groupBy('local_trips.id')
			->orderBy('local_trips.id', 'desc')
			->where(function ($query) use ($r) {
				if ($r->get('type_id')) {
					$query->where("approval_logs.approval_type_id", $r->get('type_id'))->orWhere(DB::raw("-1"), $r->get('type_id'));
				} else {
					$query->where("approval_logs.approval_type_id", 3606);
				}
			})
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
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})

			->where(function ($query) use ($from_date) {
				if (!empty($from_date)) {
					$query->where('local_trips.start_date', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('local_trips.end_date', $to_date);
				}
			})
		;
		return $lists;
	}
}
