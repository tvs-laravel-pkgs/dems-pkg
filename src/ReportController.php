<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Excel;
use Illuminate\Http\Request;
use Redirect;
use Session;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\LocalTrip;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Trip;
use Yajra\Datatables\Datatables;

class ReportController extends Controller {

	public function eyatraOutstationFilterData() {

		$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$data['outlet_list'] = collect(Outlet::select('name', 'id')->get())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 535)->where(DB::raw('LOWER(name)'), '!=', strtolower("resolved"))->where('id', '!=', 3027)->where('id', '!=', 3033)->where('id', '!=', 3035)->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);

		$outstation_start_date = session('outstation_start_date');
		$outstation_end_date = session('outstation_end_date');
		$filter_employee_id = session('outstation_employee_id') ? intval(session('outstation_employee_id')) : '';
		$data['filter_employee_id'] = ($filter_employee_id == '-1') ? '' : $filter_employee_id;
		$filter_purpose_id = session('outstation_purpose_id') ? intval(session('outstation_purpose_id')) : '';
		$data['filter_purpose_id'] = ($filter_purpose_id == '-1') ? '' : $filter_purpose_id;
		if (!$outstation_start_date) {
			// $outstation_start_date = date('01-m-Y');
			// $outstation_end_date = date('t-m-Y');
			$outstation_start_date = '';
			$outstation_end_date = '';
		}

		$data['outstation_start_date'] = $outstation_start_date;
		$data['outstation_end_date'] = $outstation_end_date;
		$data['filter_outlet_id'] = $filter_outlet_id = session('outstation_outlet_id') ? intval(session('outstation_outlet_id')) : '-1';
		$data['filter_status_id'] = $filter_status_id = session('outstation_status_id') ? intval(session('outstation_status_id')) : '3034';

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
		ob_end_clean();
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
			->join('users', 'users.entity_id', 'trips.employee_id')
			->leftJoin('bank_details', function ($join) {
				$join->on('bank_details.entity_id', 'e.id')->where('bank_details.detail_of_id',3121);
			})
			->select(
				'trips.id',
				'trips.number',
				DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'e.code as ecode',
				'users.name as ename',
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				'purpose.name as purpose',
				'ey_employee_claims.total_amount',
				'status.name as status', 'trips.advance_received', 'ey_employee_claims.amount_to_pay', 'ey_employee_claims.balance_amount',
				DB::raw('DATE_FORMAT(ey_employee_claims.claim_approval_datetime,"%d/%m/%Y %h:%i %p") as claim_approval_datetime'),
				'outlets.code',
				'bank_details.account_name','bank_details.account_number','bank_details.ifsc_code','bank_details.bank_name','bank_details.branch_name',
				DB::raw('CONCAT(outlets.code,"-",outlets.name) as outlet_name')
			)
			->where('users.user_type_id', 3121)
			->where('e.company_id', Auth::user()->company_id)
		// ->where('ey_employee_claims.status_id', 3026)
			->groupBy('trips.id');

		if ($outstation_outlet_id && $outstation_outlet_id != '-1') {
			$trips = $trips->where("e.outlet_id", $outstation_outlet_id);
		}

		if ($outstation_status_id && $outstation_status_id != '-1') {
			$trips = $trips->where("ey_employee_claims.status_id", $outstation_status_id);
		} else {
			$trips = $trips->whereIn('ey_employee_claims.status_id', [3023, 3024, 3025, 3026, 3029, 3030, 3031, 3034]);
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

		if (count($trips) > 0) {
			$trips_header = [
				'Trip ID', 
				'Employee Code', 
				'Employee Name', 
				'Outlet', 
				'Travel Period',
				'Purpose', 
				'Total Expense Amount', 
				'Advance Received', 
				'Total Claim Amount', 
				'Payment pending from',
				'Status', 
				// 'Claim Approved Date & Time',
				'Account Name',
				'Account Number',
				'IFSC Code',
				'Transaction number',
				'Transaction Date',
				'Transaction Amount'
			];
			$trips_details = array();
			if ($trips) {
				foreach ($trips as $key => $trip) {
					if ($trip->amount_to_pay == 1) {
						$pending_from = 'Company';
					} else {
						$pending_from = 'Employee';
					}
					if ($trip->advance_received > 0) {
						if ($trip->total_amount > $trip->advance_received) {
							$claim_amount = $trip->total_amount - $trip->advance_received;
						} elseif ($trip->total_amount < $trip->advance_received) {
							$claim_amount = $trip->advance_received - $trip->total_amount;
						} else {
							$claim_amount = 0;
						}
					} else {
						$claim_amount = $trip->total_amount;
					}

					$trips_detail = [
						$trip->number,
						$trip->ecode,
						$trip->ename,
						$trip->outlet_name,
						$trip->travel_period,
						$trip->purpose,
						$trip->total_amount,
						$trip->advance_received ? $trip->advance_received : '0',
						floatval($claim_amount),
						$pending_from,
						$trip->status,
						// $trip->claim_approval_datetime,
						$trip->account_name,
						$trip->account_number,
						$trip->ifsc_code,
					];

					//Check Paid or not
					$payment_detail = Payment::where('payment_of_id',3251)->where('entity_id',$trip->id)->first();
					if($payment_detail){
						$payment_details = [
							$payment_detail->reference_number,
							$payment_detail->date,
							$payment_detail->amount,
						];
					}else{
						$payment_details = [
							'',
							'',
							'',
						];
					}
					$trip_details = array_merge($trips_detail, $payment_details);

					$trips_details[] = $trip_details;
				}
			}

			// dd($trips_header, $trips_details);
			$time_stamp = date('Y_m_d_h_i_s');
			Excel::create('Outstation Trip Report' . $time_stamp, function ($excel) use ($trips_header, $trips_details) {
				$excel->sheet('Outstation Trip Report', function ($sheet) use ($trips_header, $trips_details) {
					$sheet->fromArray($trips_details, NULL, 'A1');
					$sheet->row(1, $trips_header);
					$sheet->row(1, function ($row) {
						$row->setBackground('#07c63a');
					});
				});
			})->export('xlsx');
		} else {
			Session()->flash('error', 'No Data Found');
			return Redirect::to('/#!/report/outstation-trip/list');
		}

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
					$query->whereIn('local_trips.status_id', [3023, 3024, 3026, 3030, 3034]);
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

		$data['trip_status_list'] = collect(Config::select('name', 'id')->whereIn('id', [3023, 3024, 3026, 3030, 3034])->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);

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
			->join('users', 'users.entity_id', 'local_trips.employee_id')
			->join('outlets', 'outlets.id', 'e.outlet_id')
			->leftJoin('bank_details', function ($join) {
				$join->on('bank_details.entity_id', 'e.id')->where('bank_details.detail_of_id',3121);
			})
			->select(
				'local_trips.id',
				'local_trips.number',
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'e.code as ecode',
				'users.name as ename',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				'purpose.name as purpose', 'local_trips.beta_amount', 'local_trips.travel_amount', 'local_trips.other_expense_amount', 'local_trips.claim_amount as total_amount', 'status.name as status',
				DB::raw('DATE_FORMAT(local_trips.claim_approval_datetime,"%d/%m/%Y %h:%i %p") as claim_approval_datetime'),
				'outlets.code',
				'bank_details.account_name','bank_details.account_number','bank_details.ifsc_code','bank_details.bank_name','bank_details.branch_name',
				DB::raw('CONCAT(outlets.code,"-",outlets.name) as outlet_name')
			)
			->where('users.user_type_id', 3121)
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
			$trips = $trips->whereIn('local_trips.status_id', [3023, 3024, 3026, 3030, 3034]);
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
		if (count($trips) > 0) {
			// dd($trips);
			$trips_header = [
				'Trip ID', 
				'Employee Code', 
				'Employee Name', 
				'Outlet', 
				'Travel Period', 
				'Purpose', 
				'Travel Amount', 
				'Other Expense Amount', 
				'Beta Amount', 
				'Total Amount', 
				'Status', 
				// 'Claim Approved Date & Time',
				'Account Name',
				'Account Number',
				'IFSC Code',
				'Transaction number',
				'Transaction Date',
				'Transaction Amount'
			];
			$trips_details = array();
			if ($trips) {
				foreach ($trips as $key => $trip) {
					
					$trips_detail = [
						$trip->number,
						$trip->ecode,
						$trip->ename,
						$trip->outlet_name,
						$trip->travel_period,
						$trip->purpose,
						$trip->travel_amount,
						$trip->other_expense_amount,
						$trip->beta_amount,
						floatval($trip->total_amount),
						$trip->status,
						// $trip->claim_approval_datetime,
						$trip->account_name,
						$trip->account_number,
						$trip->ifsc_code,
					];

					//Check Paid or not
					$payment_detail = Payment::where('payment_of_id',3255)->where('entity_id',$trip->id)->first();
					if($payment_detail){
						$payment_details = [
							$payment_detail->reference_number,
							$payment_detail->date,
							$payment_detail->amount,
						];
					}else{
						$payment_details = [
							'',
							'',
							'',
						];
					}
					$trip_details = array_merge($trips_detail, $payment_details);

					$trips_details[] = $trip_details;
				}
			}
			$time_stamp = date('Y_m_d_h_i_s');
			Excel::create('Local Trip Report' . $time_stamp, function ($excel) use ($trips_header, $trips_details) {
				$excel->sheet('Local Trip Report', function ($sheet) use ($trips_header, $trips_details) {
					$sheet->fromArray($trips_details, NULL, 'A1');
					$sheet->row(1, $trips_header);
					$sheet->row(1, function ($row) {
						$row->setBackground('#07c63a');
					});
				});
			})->export('xlsx');
		} else {
			Session()->flash('error', 'No Data Found');
			return Redirect::to('/#!/report/local-trip/list');
		}

	}

	public function viewTripData($trip_id) {
		$data = [];
		$trip = Trip::with([
			'visits' => function ($q) {
				$q->orderBy('visits.id');
			},
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.travelMode.travelModesCategories',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.agent.user',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'employee.user',
			'employee.manager',
			'employee.manager.user',
			'employee.user',
			'employee.designation',
			'employee.grade',
			'employee.grade.gradeEligibility',
			'purpose',
			'status',
		])
			->find($trip_id);
		// dd($trip);
		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
			$data['errors'] = ['Trip not found'];
			return response()->json($data);
		}

		$check_user = ApprovalLog::where('type_id', 3581)->where('approved_by_id', Auth::user()->entity_id)->where('entity_id', $trip_id)->first();
		if (!$check_user) {
			$data['success'] = false;
			$data['message'] = 'Trip belongs to you';
			$data['errors'] = ['Trip belongs to you'];
			return response()->json($data);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		$days = Trip::select(DB::raw('DATEDIFF(end_date,start_date)+1 as days'))->where('id', $trip_id)->first();
		$trip->days = $days->days;
		$trip->purpose_name = $trip->purpose->name;
		$trip->status_name = $trip->status->name;
		$current_date = strtotime(date('d-m-Y'));
		$claim_date = $trip->employee->grade ? $trip->employee->grade->gradeEligibility->claim_active_days : 5;

		$claim_last_date = strtotime("+" . $claim_date . " day", strtotime($trip->end_date));

		$trip_end_date = strtotime($trip->end_date);

		$data['trip'] = $trip;

		$data['success'] = true;
		return response()->json($data);
	}

	//APPROVAL LOGS
	//OUTSTATION TRIP
	public function eyatraOutstationTripVerificationFilterData() {
		$this->data['type_list'] = collect(Config::select('name', 'id')
				->where('config_type_id', 534)
				->whereIn('id', [3600, 3601])
				->get());

		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('outstation_manager_filter_employee_id') ? intval(session('outstation_manager_filter_employee_id')) : '-1';
		$this->data['filter_type_id'] = $filter_type_id = session('outstation_manager_filter_type_id') ? intval(session('outstation_manager_filter_type_id')) : '3600';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('outstation_manager_filter_purpose_id') ? intval(session('outstation_manager_filter_purpose_id')) : '-1';

		$manager_filter_start_date = session('outstation_manager_filter_start_date');
		$manager_filter_end_date = session('outstation_manager_filter_end_date');
		if (!$manager_filter_start_date) {
			$manager_filter_start_date = date('01-m-Y');
			$manager_filter_end_date = date('t-m-Y');
		}

		$this->data['manager_filter_start_date'] = $manager_filter_start_date;
		$this->data['manager_filter_end_date'] = $manager_filter_end_date;

		$this->data['success'] = true;

		return response()->json($this->data);
	}

	public function eyatraOutstationTripData(Request $r) {
		// dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['outstation_manager_filter_employee_id' => $r->employee_id]);
		}
		if ($r->type_id && $r->type_id != '<%$ctrl.filter_type_id%>') {
			session(['outstation_manager_filter_type_id' => $r->type_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['outstation_manager_filter_purpose_id' => $r->purpose_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('outstation_manager_filter_start_date', $r->from_date);
			Session::put('outstation_manager_filter_end_date', $r->to_date);
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
		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('financier_advance_employee_id') ? intval(session('financier_advance_employee_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('financier_advance_outlet_id') ? intval(session('financier_advance_outlet_id')) : '-1';

		$financier_trip_start_date = session('financier_advance_start_date');
		$financier_trip_end_date = session('financier_advance_end_date');
		if (!$financier_trip_start_date) {
			$financier_trip_start_date = date('01-m-Y');
			$financier_trip_end_date = date('t-m-Y');
		}

		$this->data['financier_trip_start_date'] = $financier_trip_start_date;
		$this->data['financier_trip_end_date'] = $financier_trip_end_date;

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function eyatraTripAdvanceRequestData(Request $r) {
		//dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['financier_advance_employee_id' => $r->employee_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['financier_advance_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('financier_advance_start_date', $r->from_date);
			Session::put('financier_advance_end_date', $r->to_date);
		}

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

	// SR MANAGER APPROVAL
	public function eyatraTripSrManagerApprovalFilterData() {
		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$this->data['employee_list'] = collect(Employee::join('trips', 'trips.employee_id', 'employees.id')
				->join('approval_logs', 'approval_logs.entity_id', 'trips.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->where('users.user_type_id', 3121)
				->where('approval_logs.type_id', 3581)
				->where('approval_logs.approval_type_id', 3602)
				->where('approval_logs.approved_by_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('sr_mngr_filter_employee_id') ? intval(session('sr_mngr_filter_employee_id')) : '-1';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('sr_mngr_filter_purpose_id') ? intval(session('sr_mngr_filter_purpose_id')) : '-1';

		$sr_mngr_filter_start_date = session('sr_mngr_filter_start_date');
		$sr_mngr_filter_end_date = session('sr_mngr_filter_end_date');
		if (!$sr_mngr_filter_start_date) {
			$sr_mngr_filter_start_date = date('01-m-Y');
			$sr_mngr_filter_end_date = date('t-m-Y');
		}

		$this->data['sr_mngr_filter_start_date'] = $sr_mngr_filter_start_date;
		$this->data['sr_mngr_filter_end_date'] = $sr_mngr_filter_end_date;

		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraTripSrManagerApprovalData(Request $r) {
		//dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['sr_mngr_filter_employee_id' => $r->employee_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['sr_mngr_filter_purpose_id' => $r->purpose_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('sr_mngr_filter_start_date', $r->from_date);
			Session::put('sr_mngr_filter_end_date', $r->to_date);
		}
		$approval_type_id = 3602;
		$lists = ApprovalLog::getTripList($r, $approval_type_id);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/outstation-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}

	// FINANCIER APPROVAL
	public function eyatraTripFinancierApprovalFilterData() {
		// dd(session('type_id'));
		// $this->data['type_id'] = (intval(session('type_id')) > 0) ? intval(session('type_id')) : 3600;

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

	public function eyatraTripFinancierApprovalData(Request $r) {
		//dd($r->all());
		$approval_type_id = 3603;
		$lists = ApprovalLog::getTripList($r, $approval_type_id);

		//$lists = ApprovalLog::getTripAdvanceList($r);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/outstation-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}

	//VERIFIER >> OUTSTATION TRIP
	public function eyatraVerifierFilterData($id) {
		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		if ($id == '1') {
			$this->data['filter_employee_id'] = $filter_employee_id = session('verifier_local_employee_id') ? intval(session('verifier_local_employee_id')) : '-1';
			$this->data['filter_purpose_id'] = $filter_purpose_id = session('verifier_local_purpose_id') ? intval(session('verifier_local_purpose_id')) : '-1';
			$this->data['filter_outlet_id'] = $filter_outlet_id = session('verifier_local_outlet_id') ? intval(session('verifier_local_outlet_id')) : '-1';

			$verifier_local_start_date = session('verifier_local_start_date');
			$verifier_local_end_date = session('verifier_local_end_date');
			if (!$verifier_local_start_date) {
				$verifier_local_start_date = date('01-m-Y');
				$verifier_local_end_date = date('t-m-Y');
			}

			$this->data['verifier_local_start_date'] = $verifier_local_start_date;
			$this->data['verifier_local_end_date'] = $verifier_local_end_date;
		} else {
			$this->data['filter_employee_id'] = $filter_employee_id = session('verifier_outstation_employee_id') ? intval(session('verifier_outstation_employee_id')) : '-1';
			$this->data['filter_purpose_id'] = $filter_purpose_id = session('verifier_outstation_purpose_id') ? intval(session('verifier_outstation_purpose_id')) : '-1';
			$this->data['filter_outlet_id'] = $filter_outlet_id = session('verifier_outstation_outlet_id') ? intval(session('verifier_outstation_outlet_id')) : '-1';

			$verifier_outstation_start_date = session('verifier_outstation_start_date');
			$verifier_outstation_end_date = session('verifier_outstation_end_date');
			if (!$verifier_outstation_start_date) {
				$verifier_outstation_start_date = date('01-m-Y');
				$verifier_outstation_end_date = date('t-m-Y');
			}

			$this->data['verifier_outstation_start_date'] = $verifier_outstation_start_date;
			$this->data['verifier_outstation_end_date'] = $verifier_outstation_end_date;
		}

		$this->data['success'] = true;
		// dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraVerifierOutstationData(Request $r) {
		// dd($r->all());

		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['verifier_outstation_employee_id' => $r->employee_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['verifier_outstation_purpose_id' => $r->purpose_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['verifier_outstation_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('verifier_outstation_start_date', $r->from_date);
			Session::put('verifier_outstation_end_date', $r->to_date);
		}

		$approval_type_id = 3622;
		$lists = ApprovalLog::getTripList($r, $approval_type_id);

		//$lists = ApprovalLog::getTripAdvanceList($r);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/outstation-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}

	public function eyatraVerifierLocalData(Request $r) {
		// dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['verifier_local_employee_id' => $r->employee_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['verifier_local_purpose_id' => $r->purpose_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['verifier_local_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('verifier_local_start_date', $r->from_date);
			Session::put('verifier_local_end_date', $r->to_date);
		}
		$approval_type_id = 3623;
		$lists = ApprovalLog::getFinancierLocalTripList($r, $approval_type_id);

		//$lists = ApprovalLog::getTripAdvanceList($r);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/report/local-trip-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}

	//OUTSTATION TRIP  FINANCIER PAID
	public function eyatraTripFinancierPaidFilterData() {
		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('financier_paid_employee_id') ? intval(session('financier_paid_employee_id')) : '-1';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('financier_paid_purpose_id') ? intval(session('financier_paid_purpose_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('financier_paid_outlet_id') ? intval(session('financier_paid_outlet_id')) : '-1';

		$financier_paid_start_date = session('financier_paid_start_date');
		$financier_paid_end_date = session('financier_paid_end_date');
		if (!$financier_paid_start_date) {
			$financier_paid_start_date = date('01-m-Y');
			$financier_paid_end_date = date('t-m-Y');
		}

		$this->data['financier_paid_start_date'] = $financier_paid_start_date;
		$this->data['financier_paid_end_date'] = $financier_paid_end_date;

		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraTripFinancierPaidData(Request $r) {
		// dd($r->all());

		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['financier_paid_employee_id' => $r->employee_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['financier_paid_purpose_id' => $r->purpose_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['financier_paid_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('financier_paid_start_date', $r->from_date);
			Session::put('financier_paid_end_date', $r->to_date);
		}

		$approval_type_id = 3604;
		$lists = ApprovalLog::getTripList($r, $approval_type_id);

		//$lists = ApprovalLog::getTripAdvanceList($r);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/outstation-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}

	//LOCAL TRIP FINANCIER PAID
	public function eyatraLocalTripFinancierPaidFilterData() {
		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('financier_local_paid_employee_id') ? intval(session('financier_local_paid_employee_id')) : '-1';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('financier_local_paid_purpose_id') ? intval(session('financier_local_paid_purpose_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('financier_local_paid_outlet_id') ? intval(session('financier_local_paid_outlet_id')) : '-1';

		$financier_local_paid_start_date = session('financier_local_paid_start_date');
		$financier_local_paid_end_date = session('financier_local_paid_end_date');
		if (!$financier_local_paid_start_date) {
			$financier_local_paid_start_date = date('01-m-Y');
			$financier_local_paid_end_date = date('t-m-Y');
		}

		$this->data['financier_local_paid_start_date'] = $financier_local_paid_start_date;
		$this->data['financier_local_paid_end_date'] = $financier_local_paid_end_date;

		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraLocalTripFinancierPaidData(Request $r) {
		// dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['financier_local_paid_employee_id' => $r->employee_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['financier_local_paid_purpose_id' => $r->purpose_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['financier_local_paid_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('financier_local_paid_start_date', $r->from_date);
			Session::put('financier_local_paid_end_date', $r->to_date);
		}
		$approval_type_id = 3608;
		$lists = ApprovalLog::getFinancierLocalTripList($r, $approval_type_id);

		//$lists = ApprovalLog::getTripAdvanceList($r);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/report/local-trip-claim/view/' . $list->entity_id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';

			})
			->make(true);
	}

	//OUTSTATION TRIP EMPLOYEE PAID
	public function eyatraTripEmployeePaidFilterData() {
		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('employee_paid_employee_id') ? intval(session('employee_paid_employee_id')) : '-1';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('employee_paid_purpose_id') ? intval(session('employee_paid_purpose_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('employee_paid_outlet_id') ? intval(session('employee_paid_outlet_id')) : '-1';

		$employee_paid_start_date = session('employee_paid_start_date');
		$employee_paid_end_date = session('employee_paid_end_date');
		if (!$employee_paid_start_date) {
			$employee_paid_start_date = date('01-m-Y');
			$employee_paid_end_date = date('t-m-Y');
		}

		$this->data['employee_paid_start_date'] = $employee_paid_start_date;
		$this->data['employee_paid_end_date'] = $employee_paid_end_date;

		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraTripEmployeePaidData(Request $r) {
		//dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['employee_paid_employee_id' => $r->employee_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['employee_paid_purpose_id' => $r->purpose_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['employee_paid_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('employee_paid_start_date', $r->from_date);
			Session::put('employee_paid_end_date', $r->to_date);
		}
		$approval_type_id = 3605;
		$lists = ApprovalLog::getTripList($r, $approval_type_id);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
						<a href="#!/outstation-claim/view/' . $list->entity_id . '">
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

		$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$this->data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		$this->data['filter_employee_id'] = $filter_employee_id = session('local_manager_filter_employee_id') ? intval(session('local_manager_filter_employee_id')) : '-1';
		$this->data['filter_type_id'] = $filter_type_id = session('local_manager_filter_type_id') ? intval(session('local_manager_filter_type_id')) : '3606';
		$this->data['filter_purpose_id'] = $filter_purpose_id = session('local_manager_filter_purpose_id') ? intval(session('local_manager_filter_purpose_id')) : '-1';

		$manager_filter_start_date = session('local_manager_filter_start_date');
		$manager_filter_end_date = session('local_manager_filter_end_date');
		if (!$manager_filter_start_date) {
			$manager_filter_start_date = date('01-m-Y');
			$manager_filter_end_date = date('t-m-Y');
		}

		$this->data['manager_filter_start_date'] = $manager_filter_start_date;
		$this->data['manager_filter_end_date'] = $manager_filter_end_date;

		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function eyatraLocalTripData(Request $r) {

		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['local_manager_filter_employee_id' => $r->employee_id]);
		}
		if ($r->type_id && $r->type_id != '<%$ctrl.filter_type_id%>') {
			session(['local_manager_filter_type_id' => $r->type_id]);
		}
		if ($r->purpose_id && $r->purpose_id != '<%$ctrl.filter_purpose_id%>') {
			session(['local_manager_filter_purpose_id' => $r->purpose_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('local_manager_filter_start_date', $r->from_date);
			Session::put('local_manager_filter_end_date', $r->to_date);
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

	//PETTY CASH FILTER DATA
	public function eyatraPettyCashFilterData($id) {
		//Manager Filter Data
		if ($id == 1) {
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.reporting_to_id', Auth::user()->entity_id)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		}
		if ($id == 2) {
			$outlet_id = Employee::where('id', Auth::user()->entity_id)->pluck('outlet_id')->first();
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.outlet_id', $outlet_id)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		}
		if ($id == 3) {
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
			$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);
		}
		$this->data['filter_employee_id'] = $filter_employee_id = session('petty_cash_employee_id') ? intval(session('petty_cash_employee_id')) : '-1';
		$this->data['filter_type_id'] = $filter_employee_id = session('petty_cash_type_id') ? intval(session('petty_cash_type_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('petty_cash_outlet_id') ? intval(session('petty_cash_outlet_id')) : '-1';

		$petty_cash_start_date = session('petty_cash_start_date');
		$petty_cash_end_date = session('petty_cash_end_date');
		if (!$petty_cash_start_date) {
			$petty_cash_start_date = date('01-m-Y');
			$petty_cash_end_date = date('t-m-Y');
		}

		$this->data['petty_cash_start_date'] = $petty_cash_start_date;
		$this->data['petty_cash_end_date'] = $petty_cash_end_date;

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function eyatraPettyCashData(Request $r) {

		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['petty_cash_employee_id' => $r->employee_id]);
		}
		if ($r->type_id && $r->type_id != '<%$ctrl.filter_type_id%>') {
			session(['petty_cash_type_id' => $r->type_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['petty_cash_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('petty_cash_start_date', $r->from_date);
			Session::put('petty_cash_end_date', $r->to_date);
		}

		if ($r->list_type == 1) {
			$approval_type_id = [3609, 3612];
		} elseif ($r->list_type == 2) {
			$approval_type_id = [3610, 3621];
		} elseif ($r->list_type == 3) {
			$approval_type_id = [3611, 3613];
		}

		// dd($approval_type_id);
		$lists = ApprovalLog::getPettyCashList($r, $approval_type_id);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {
				$type_id = $list->petty_cash_type_id == '3440' ? 1 : 2;
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				return '
						<a href="#!/petty-cash/view/' . $type_id . '/' . $list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
			})
			->make(true);

	}

	//EXPENSE VOUCHER ADVANCE FILTER DATA
	public function eyatraExpenseVoucherAdvanceFilterData($id) {
		//dd($id);
		if ($id == 1) {
			//Manager Filter Data
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.reporting_to_id', Auth::user()->entity_id)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		}
		if ($id == 2) {
			//Cashier Filter Data
			$outlet_id = Employee::where('id', Auth::user()->entity_id)->pluck('outlet_id')->first();
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.outlet_id', $outlet_id)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		}
		if ($id == 3) {
			//Financier Filter Data
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
			$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);
		}
		$this->data['filter_employee_id'] = $filter_employee_id = session('eva_employee_id') ? intval(session('eva_employee_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('eva_outlet_id') ? intval(session('eva_outlet_id')) : '-1';

		$petty_cash_start_date = session('eva_start_date');
		$petty_cash_end_date = session('eva_end_date');
		if (!$petty_cash_start_date) {
			$petty_cash_start_date = date('01-m-Y');
			$petty_cash_end_date = date('t-m-Y');
		}

		$this->data['eva_start_date'] = $petty_cash_start_date;
		$this->data['eva_end_date'] = $petty_cash_end_date;

		$this->data['success'] = true;
		return response()->json($this->data);
	}
	//EXPENSE VOUCHER ADVANCE MANAGER
	public function eyatraExpenseVoucherAdvanceData(Request $r) {
		//dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['eva_employee_id' => $r->employee_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['eva_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('eva_start_date', $r->from_date);
			Session::put('eva_end_date', $r->to_date);
		}

		if ($r->list_type == 1) {
			$approval_type_id = [3614, 3617];
		} elseif ($r->list_type == 2) {
			$approval_type_id = [3615, 3618];
		} elseif ($r->list_type == 3) {
			$approval_type_id = [3616, 3619];
		}

		//dd($approval_type_id);
		$lists = ApprovalLog::getExpenseVoucherAdvanceList($r, $approval_type_id);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				return '
						<a href="#!/expense/voucher-advance/view/' . $list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
			})
			->make(true);

	}
	//EXPENSE VOUCHER ADVANCE REPAID FILTER DATA
	public function eyatraExpenseVoucherAdvanceRepaidFilterData($id) {
		//dd($id);
		if ($id == 1) {
			//Cashier Filter Data
			$outlet_id = Employee::where('id', Auth::user()->entity_id)->pluck('outlet_id')->first();
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.outlet_id', $outlet_id)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		}
		if ($id == 2) {
			//Financier Filter Data
			$this->data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
			$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '-1', 'name' => 'Select Outlet']);
		}
		$this->data['filter_employee_id'] = $filter_employee_id = session('eva_repaid_employee_id') ? intval(session('eva_repaid_employee_id')) : '-1';
		$this->data['filter_outlet_id'] = $filter_outlet_id = session('eva_repaid_outlet_id') ? intval(session('eva_repaid_outlet_id')) : '-1';

		$petty_cash_start_date = session('eva_repaid_start_date');
		$petty_cash_end_date = session('eva_repaid_end_date');
		if (!$petty_cash_start_date) {
			$petty_cash_start_date = date('01-m-Y');
			$petty_cash_end_date = date('t-m-Y');
		}

		$this->data['eva_repaid_start_date'] = $petty_cash_start_date;
		$this->data['eva_repaid_end_date'] = $petty_cash_end_date;

		$this->data['success'] = true;
		return response()->json($this->data);
	}
	//EXPENSE VOUCHER ADVANCE REPAID
	public function eyatraExpenseVoucherAdvanceRepaidData(Request $r) {
		//dd($r->all());
		if ($r->employee_id && $r->employee_id != '<%$ctrl.filter_employee_id%>') {
			session(['eva_repaid_employee_id' => $r->employee_id]);
		}
		if ($r->outlet_id && $r->outlet_id != '<%$ctrl.filter_outlet_id%>') {
			session(['eva_repaid_outlet_id' => $r->outlet_id]);
		}
		if ($r->from_date != '<%$ctrl.start_date%>') {
			Session::put('eva_repaid_start_date', $r->from_date);
			Session::put('eva_repaid_end_date', $r->to_date);
		}
		if ($r->list_type == 2) {
			//Cashier
			$approval_type_id = [3258];
		} elseif ($r->list_type == 3) {
			//Financier
			$approval_type_id = [3258];
		}
		//dd($approval_type_id);
		$lists = ApprovalLog::getExpenseVoucherAdvanceRepaidList($r, $approval_type_id);
		return Datatables::of($lists)
			->addColumn('action', function ($list) {
				$type_id = 1;
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				return '
						<a href="#!/expense/voucher-advance/view/' . $list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
			})
			->make(true);

	}

}
