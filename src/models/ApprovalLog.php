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
		//dd('in');
		$approvalLog = ApprovalLog::firstOrNew([
			'type_id' => $type_id,
			'entity_id' => $entity_id,
			'approval_type_id' => $approval_type_id,
		]);
		// $approvalLog->type_id = $type_id;
		// $approvalLog->entity_id = $entity_id;
		// $approvalLog->approval_type_id = $approval_type_id;
		$approvalLog->approved_by_id = $approved_by_id;
		$approvalLog->approved_at = $approved_at;
		$approvalLog->save();

	}
	public static function getOutstationList($r) {
		if (!empty($r->from_date) && $r->from_date != '<%$ctrl.start_date%>') {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date) && $r->to_date != '<%$ctrl.end_date%>') {
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

	public static function getTripList($r, $approval_type_id) {
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
				DB::raw('DATE_FORMAT(trips.end_date,"%d-%m-%Y") as end_date'),
				'trips.status_id',
				'purpose.name as purpose',
				'status.name as status', 'status.name as status_name',
				DB::raw('DATE_FORMAT(approval_logs.approved_at,"%d-%m-%Y %h:%i:%s %p") as date'),
				'approval_logs.approval_type_id as type_id', 'ey_employee_claims.total_amount'
			)
			->where('users.user_type_id', 3121)
			->where('approval_logs.type_id', 3581)
			->where('approval_logs.approval_type_id', $approval_type_id)
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

	public static function getFinancierLocalTripList($r, $approval_type_id) {
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
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->leftJoin('users', 'users.entity_id', 'e.id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->leftJoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'approval_logs.entity_id')
			->select(
				'approval_logs.id as id',
				'approval_logs.entity_id as entity_id',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('DATE_FORMAT(local_trips.start_date,"%d-%m-%Y") as start_date'),
				DB::raw('DATE_FORMAT(local_trips.end_date,"%d-%m-%Y") as end_date'),
				'local_trips.status_id',
				'purpose.name as purpose',
				'status.name as status', 'status.name as status_name',
				DB::raw('DATE_FORMAT(approval_logs.approved_at,"%d-%m-%Y %h:%i:%s %p") as date'),
				'approval_logs.approval_type_id as type_id', 'local_trips.claim_amount as total_amount'
			)
			->where('users.user_type_id', 3121)
			->where('approval_logs.type_id', 3582)
			->where('approval_logs.approval_type_id', $approval_type_id)
			->where('approval_logs.approved_by_id', Auth::user()->entity_id)
			->groupBy('local_trips.id')
			->orderBy('local_trips.created_at', 'desc')
			->orderBy('local_trips.status_id', 'desc')
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
					$query->where('local_trips.start_date', '>=', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('local_trips.end_date', '<=', $to_date);
				}
			})
		;
		return $lists;
	}

	public static function getTripAdvanceList($r) {
		if (!empty($r->from_date) && $r->from_date != '<%$ctrl.start_date%>') {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date) && $r->from_date != '<%$ctrl.end_date%>') {
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
		if (!empty($r->from_date) && $r->from_date != '<%$ctrl.start_date%>') {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date) && $r->to_date != '<%$ctrl.end_date%>') {
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
					$query->where('local_trips.start_date', '>=', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('local_trips.end_date', '<=', $to_date);
				}
			})
		;
		return $lists;
	}

	public static function getPettyCashList($r, $approval_type_id) {
		// dd($r->all());
		if (!empty($r->from_date) && $r->from_date != '<%$ctrl.start_date%>') {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date) && $r->from_date != '<%$ctrl.end_date%>') {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}

		$lists = ApprovalLog::join('petty_cash', 'petty_cash.id', 'approval_logs.entity_id')
			->select(
				'petty_cash.id',
				DB::raw('DATE_FORMAT(petty_cash.date , "%d-%m-%Y")as date'),
				'petty_cash.total',
				'users.name as ename',
				'outlets.name as outlet_name',
				'employees.code as ecode',
				'petty_cash_type.name as petty_cash_type', DB::raw('DATE_FORMAT(approval_logs.approved_at,"%d-%m-%Y %h:%i:%s %p") as approval_date'), 'petty_cash_type.id as petty_cash_type_id'
			)
			->leftJoin('configs', 'configs.id', 'petty_cash.status_id')
			->leftJoin('configs as petty_cash_type', 'petty_cash_type.id', 'petty_cash.petty_cash_type_id')
			->join('employees', 'employees.id', 'petty_cash.employee_id')
			->join('users', 'users.entity_id', 'employees.id')
			->join('outlets', 'outlets.id', 'employees.outlet_id')
			->where('approval_logs.approved_by_id', Auth::user()->entity_id)
			->whereIn('approval_logs.approval_type_id', $approval_type_id)
			->where('users.user_type_id', 3121)
			->orderBy('approval_logs.id', 'desc')
			->where(function ($query) use ($r) {
				if ($r->get('type_id') && $r->get('type_id') != '<%$ctrl.filter_type_id%>') {
					$query->where("approval_logs.type_id", $r->get('type_id'))->orWhere(DB::raw("-1"), $r->get('type_id'));
				} else {
					$query->whereIn("approval_logs.type_id", [3583, 3584]);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('employee_id') && $r->get('employee_id') != '<%$ctrl.filter_employee_id%>') {
					$query->where("employees.id", $r->get('employee_id'))->orWhere(DB::raw("-1"), $r->get('employee_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('outlet_id')) {
					$query->where("employees.outlet_id", $r->get('outlet_id'))->orWhere(DB::raw("-1"), $r->get('outlet_id'));
				}
			})

			->where(function ($query) use ($from_date) {
				if (!empty($from_date)) {
					$query->where('petty_cash.date', '>=', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('petty_cash.date', '<=', $to_date);
				}
			})
		;
		return $lists;
	}
}
