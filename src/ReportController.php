<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Excel;
use Illuminate\Http\Request;
use Session;
use Uitoux\EYatra\LocalTrip;
use Yajra\Datatables\Datatables;

class ReportController extends Controller {

	public function eyatraOutstationFilterData() {

		$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$data['outlet_list'] = collect(Outlet::select('name', 'id')->get())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$outstation_start_date = session('outstation_start_date');
		$outstation_end_date = session('outstation_end_date');
		$filter_employee_id = session('outstation_employee_id') ? intval(session('outstation_employee_id')) : '';
		$data['filter_employee_id'] = ($filter_employee_id == '-1') ? '' : $filter_employee_id;
		$filter_purpose_id = session('outstation_purpose_id') ? intval(session('outstation_purpose_id')) : '';
		$data['filter_purpose_id'] = ($filter_purpose_id == '-1') ? '' : $filter_purpose_id;
		if (!$outstation_start_date) {
			$outstation_start_date = date('01-m-Y');
			$outstation_end_date = date('t-m-Y');
		}

		$data['outstation_start_date'] = $outstation_start_date;
		$data['outstation_end_date'] = $outstation_end_date;
		$data['filter_outlet_id'] = $filter_outlet_id = session('outstation_outlet_id') ? intval(session('outstation_outlet_id')) : '-1';
		$data['filter_status_id'] = $filter_status_id = session('outstation_status_id') ? intval(session('outstation_status_id')) : '-1';

		$data['success'] = true;
		return response()->json($data);
	}

	public function getEmployeeByOutlet(Request $r) {
		$employee_list = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where(function ($query) use ($r) {
					if ($r->outlet_id != '-1') {
						$query->where('employees.outlet_id', $r->outlet_id);

					}
				})
				->where('employees.company_id', Auth::user()->company_id)
				->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$data['employee_list'] = $employee_list;
		$data['success'] = true;
		return response()->json($data);
	}
	public function listOutstationTripReport(Request $r) {

		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('outstation_start_date', $r->from_date);
			Session::put('outstation_end_date', $r->to_date);
		}
		if ($r->employee_id != '<%$ctrl.filter_employee_id%>') {
			Session::put('outstation_employee_id', $r->employee_id);
			Session::put('outstation_purpose_id', $r->purpose_id);
		}
		if ($r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			Session::put('outstation_outlet_id', $r->outlet_id);
		}
		if ($r->status_id != '<%$ctrl.filter_status_id%>') {
			Session::put('outstation_status_id', $r->status_id);
		}

		$trips = EmployeeClaim::leftJoin('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->leftJoin('visits as v', 'v.trip_id', 'trips.id')
			->leftJoin('ncities as c', 'c.id', 'v.from_city_id')
			->leftJoin('employees as e', 'e.id', 'trips.employee_id')
			->leftJoin('outlets', 'outlets.id', 'e.outlet_id')
			->leftJoin('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->leftJoin('configs as status', 'status.id', 'ey_employee_claims.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'e.code as ecode',
				'users.name as ename',
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				'purpose.name as purpose',
				'ey_employee_claims.total_amount', DB::raw('CONCAT(outlets.code,"-",outlets.name) as outlet_name'),
				'status.name as status',
				DB::raw('DATE_FORMAT(ey_employee_claims.claim_approval_datetime,"%d/%m/%Y %h:%i %p") as claim_approval_datetime')
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
				if ($r->from_date && $r->from_date != '<%$ctrl.start_date%>') {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date && $r->to_date != '<%$ctrl.end_date%>') {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('outlet_id') && $r->get('employee_id') != '<%$ctrl.filter_outlet_id%>') {
					$query->where("e.outlet_id", $r->get('outlet_id'))->orWhere(DB::raw("-1"), $r->get('outlet_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id') && (($r->get('status_id') != '<%$ctrl.filter_status_id%>') && ($r->get('status_id') != -1))) {
					$query->where("ey_employee_claims.status_id", $r->get('status_id'));
				} else {
					$query->whereIn('ey_employee_claims.status_id', [3023, 3030, 3026]);
				}
			})
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc');

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');

				return '
				<a href="#!/trip/claim/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function outstationTripExport() {

		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);

		$employee_id = session('outstation_employee_id');
		$purpose_id = session('outstation_purpose_id');
		$outstation_start_date = session('outstation_start_date');
		$outstation_end_date = session('outstation_end_date');
		$outstation_outlet_id = session('outstation_outlet_id');
		$outstation_status_id = session('outstation_status_id');
		// dd($employee_id, $purpose_id, $outstation_start_date, $outstation_end_date, $outstation_outlet_id, $outstation_status_id);

		$trips = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('outlets', 'outlets.id', 'e.outlet_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'ey_employee_claims.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'e.code as ecode',
				'users.name as ename',
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				'purpose.name as purpose',
				'ey_employee_claims.total_amount',
				'status.name as status',
				DB::raw('DATE_FORMAT(ey_employee_claims.claim_approval_datetime,"%d/%m/%Y %h:%i %p") as claim_approval_datetime'),
				DB::raw('CONCAT(outlets.code,"-",outlets.name) as outlet_name')
			)
			->where('e.company_id', Auth::user()->company_id)
		// ->where('ey_employee_claims.status_id', 3026)
			->groupBy('trips.id');

		if ($outstation_outlet_id && $outstation_outlet_id != '-1') {
			$trips = $trips->where("e.outlet_id", $outstation_outlet_id);
		}

		if ($outstation_status_id && $outstation_status_id != '-1') {
			$trips = $trips->where("ey_employee_claims.status_id", $outstation_status_id);
		} else {
			$trips = $trips->whereIn('ey_employee_claims.status_id', [3023, 3030, 3026]);
		}

		if ($employee_id && $employee_id != '-1') {
			$trips = $trips->where("e.id", $employee_id);
		}
		if ($purpose_id && $purpose_id != '-1') {
			$trips = $trips->where("purpose.id", $purpose_id);
		}
		if ($outstation_start_date) {
			$date = date('Y-m-d', strtotime($outstation_start_date));
			$trips = $trips->where("trips.start_date", '>=', $date);
		}
		if ($outstation_end_date) {
			$date = date('Y-m-d', strtotime($outstation_end_date));
			$trips = $trips->where("trips.end_date", '<=', $date);
		}

		$trips = $trips->get();

		$trips_header = ['Trip ID', 'Employee Code', 'Employee Name', 'Outlet', 'Travel Period', 'Purpose', 'Total Amount', 'Status', 'Claim Approved Date & Time'];
		$trips_details = array();
		if ($trips) {
			foreach ($trips as $key => $trip) {
				$trips_details[] = [
					$trip->number,
					$trip->ecode,
					$trip->ename,
					$trip->outlet_name,
					$trip->travel_period,
					$trip->purpose,
					$trip->total_amount,
					$trip->status,
					$trip->claim_approval_datetime,
				];
			}
		}

		// dd($trips_header, $trips_details);
		Excel::create('Outstation Trip Report', function ($excel) use ($trips_header, $trips_details) {
			$excel->sheet('Outstation Trip Report', function ($sheet) use ($trips_header, $trips_details) {
				$sheet->fromArray($trips_details, NULL, 'A1');
				$sheet->row(1, $trips_header);
				$sheet->row(1, function ($row) {
					$row->setBackground('#07c63a');
				});
			});
		})->export('xls');

	}

	public function listLocalTripReport(Request $r) {

		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('local_start_date', $r->from_date);
			Session::put('local_end_date', $r->to_date);
		}
		if ($r->employee_id != '<%$ctrl.filter_employee_id%>') {
			Session::put('local_employee_id', $r->employee_id);
			Session::put('local_purpose_id', $r->purpose_id);
		}
		if ($r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			Session::put('local_outlet_id', $r->outlet_id);
		}
		if ($r->status_id != '<%$ctrl.filter_status_id%>') {
			Session::put('local_status_id', $r->status_id);
		}

		$trips = LocalTrip::from('local_trips')
			->leftJoin('employees as e', 'e.id', 'local_trips.employee_id')
			->leftJoin('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->leftJoin('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->leftJoin('outlets', 'outlets.id', 'e.outlet_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.number',
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'e.code as ecode',
				'users.name as ename',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				'purpose.name as purpose',
				'local_trips.claim_amount as total_amount', 'status.name as status',
				DB::raw('DATE_FORMAT(local_trips.claim_approval_datetime,"%d/%m/%Y %h:%i %p") as claim_approval_datetime')

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
				if ($r->from_date && $r->from_date != '<%$ctrl.start_date%>') {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("local_trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date && $r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("local_trips.end_date", '<=', $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('outlet_id') && $r->get('employee_id') != '<%$ctrl.filter_outlet_id%>') {
					$query->where("e.outlet_id", $r->get('outlet_id'))->orWhere(DB::raw("-1"), $r->get('outlet_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id') && (($r->get('status_id') != '<%$ctrl.filter_status_id%>') && ($r->get('status_id') != -1))) {
					$query->where("local_trips.status_id", $r->get('status_id'));
				} else {
					$query->whereIn('local_trips.status_id', [3023, 3030, 3026]);
				}
			})
		// ->where('local_trips.status_id', 3026)
			->groupBy('local_trips.id')
			->orderBy('local_trips.created_at', 'desc');

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				return '
				<a href="#!/local-trip/claim/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function eyatraLocalFilterData() {

		$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$data['outlet_list'] = collect(Outlet::select('name', 'id')->get())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$data['success'] = true;

		$local_start_date = session('local_start_date');
		$local_end_date = session('local_end_date');

		$filter_employee_id = session('local_employee_id') ? intval(session('local_employee_id')) : '';
		$data['filter_employee_id'] = ($filter_employee_id == '-1') ? '' : $filter_employee_id;
		$filter_purpose_id = session('local_purpose_id') ? intval(session('local_purpose_id')) : '';
		$data['filter_purpose_id'] = ($filter_purpose_id == '-1') ? '' : $filter_purpose_id;

		if (!$local_start_date) {
			$local_start_date = date('01-m-Y');
			$local_end_date = date('t-m-Y');
		}

		$data['filter_outlet_id'] = $filter_outlet_id = session('local_outlet_id') ? intval(session('local_outlet_id')) : '-1';
		$data['filter_status_id'] = $filter_status_id = session('local_status_id') ? intval(session('local_status_id')) : '-1';

		$data['local_trip_start_date'] = $local_start_date;
		$data['local_trip_end_date'] = $local_end_date;

		return response()->json($data);
	}

	public function localTripExport() {

		ini_set('memory_limit', '-1');
		ini_set('max_execution_time', 0);
		ob_end_clean();

		$employee_id = session('local_employee_id');
		$purpose_id = session('local_purpose_id');
		$local_start_date = session('local_start_date');
		$local_end_date = session('local_end_date');
		$local_outlet_id = session('local_outlet_id');
		$local_status_id = session('local_status_id');
		// dd($employee_id, $purpose_id, $local_start_date, $local_end_date);

		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->leftJoin('outlets', 'outlets.id', 'e.outlet_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.number',
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'e.code as ecode',
				'users.name as ename',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				'purpose.name as purpose',
				'local_trips.claim_amount as total_amount', 'status.name as status',
				DB::raw('DATE_FORMAT(local_trips.claim_approval_datetime,"%d/%m/%Y %h:%i %p") as claim_approval_datetime'),
				DB::raw('CONCAT(outlets.code,"-",outlets.name) as outlet_name')
			)
			->where('e.company_id', Auth::user()->company_id)
		// ->where('local_trips.status_id', 3026)
			->groupBy('local_trips.id')
			->orderBy('local_trips.created_at', 'desc');

		if ($local_outlet_id && $local_outlet_id != '-1') {
			$trips = $trips->where("e.outlet_id", $local_outlet_id);
		}

		if ($local_status_id && $local_status_id != '-1') {
			$trips = $trips->where("local_trips.status_id", $local_status_id);
		} else {
			$trips = $trips->whereIn('local_trips.status_id', [3023, 3030, 3026]);
		}

		if ($employee_id && $employee_id != '-1') {
			$trips = $trips->where("e.id", $employee_id);
		}
		if ($purpose_id && $purpose_id != '-1') {
			$trips = $trips->where("purpose.id", $purpose_id);
		}
		if ($local_start_date) {
			$date = date('Y-m-d', strtotime($local_start_date));
			$trips = $trips->where("local_trips.start_date", '>=', $date);
		}
		if ($local_end_date) {
			$date = date('Y-m-d', strtotime($local_end_date));
			$trips = $trips->where("local_trips.end_date", '<=', $date);
		}

		$trips = $trips->get();

		// dd($trips);
		$trips_header = ['Trip ID', 'Employee Code', 'Employee Name', 'Outlet', 'Travel Period', 'Purpose', 'Total Amount', 'Status', 'Claim Approved Date & Time'];
		$trips_details = array();
		if ($trips) {
			foreach ($trips as $key => $trip) {
				$trips_details[] = [
					$trip->number,
					$trip->ecode,
					$trip->ename,
					$trip->outlet_name,
					$trip->travel_period,
					$trip->purpose,
					$trip->total_amount,
					$trip->status,
					$trip->claim_approval_datetime,
				];
			}
		}

		Excel::create('Local Trip Report', function ($excel) use ($trips_header, $trips_details) {
			$excel->sheet('Local Trip Report', function ($sheet) use ($trips_header, $trips_details) {
				$sheet->fromArray($trips_details, NULL, 'A1');
				$sheet->row(1, $trips_header);
				$sheet->row(1, function ($row) {
					$row->setBackground('#07c63a');
				});
			});
		})->export('xls');

	}

	//APPROVAL LOGS
	//OUTSTATION TRIP
	public function eyatraOutstationTripVerificationFilterData() {
		$this->data['type_list'] = collect(Config::select('name', 'id')
				->where('config_type_id', 534)
				->whereIn('id', [3600, 3601])
				->get());
		// dd(session('type_id'));
		$this->data['type_id'] = (intval(session('type_id')) > 0) ? intval(session('type_id')) : 3600;

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
	


	public function eyatraOutstationTripData(Request $r) {

		if ($r->type_id) {
			session(['type_id' => $r->type_id]);
		}

		$lists = ApprovalLog::getOutstationList($r);
		return Datatables::of($lists)
			->addColumn('type', function ($list) {
				if ($list->type_id == 3600) {
					return "Trip";
				} else {
					return "Claim";
				}
			})
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				if ($list->type_id == 3600) {
					return '
						<a href="#!/outstation-trip/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
				} else {
					return '
						<a href="#!/outstation-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
				}

			})
			->make(true);
	}
	//APPROVAL LOGS
	//TRIP ADVANCE REQUEST
	public function eyatraTripAdvanceRequestFilterData() {
		// dd(session('type_id'));
		$this->data['type_id'] = (intval(session('type_id')) > 0) ? intval(session('type_id')) : 3600;

		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				//->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		$this->data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 501)->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}


	public function eyatraTripAdvanceRequestData(Request $r) {
		//dd($r->all());
		$lists = ApprovalLog::getTripAdvanceList($r);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
					return '
						<a href="#!/outstation-trip/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}


	//LOCAL TRIP
	public function eyatraReportLocalTripFilterData() {
		$this->data['type_list'] = collect(Config::select('name', 'id')
				->where('config_type_id', 534)
				->whereIn('id', [3606, 3607])
				->get());
		// dd(session('type_id'));
		$this->data['type_id'] = (intval(session('type_id')) > 0) ? intval(session('type_id')) : 3606;

		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraLocalTripData(Request $r) {

		if ($r->type_id) {
			session(['type_id' => $r->type_id]);
		}

		$lists = ApprovalLog::getLocalTripList($r);
		return Datatables::of($lists)
			->addColumn('type', function ($list) {
				if ($list->type_id == 3606) {
					return "Trip";
				} else {
					return "Claim";
				}
			})
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				if ($list->type_id == 3606) {
					return '
						<a href="#!/report/local-trip/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
				} else {
					return '
						<a href="#!/report/local-trip-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
				}

			})
			->make(true);
	}
}
