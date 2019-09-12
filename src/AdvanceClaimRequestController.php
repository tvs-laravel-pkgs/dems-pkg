<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Excel;
use Illuminate\Http\Request;
use Uitoux\EYatra\Payment;
use Uitoux\EYatra\Trip;
use Yajra\Datatables\Datatables;

class AdvanceClaimRequestController extends Controller {
	public function listAdvanceClaimRequest(Request $r) {
		$trips = Trip::join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'trips.start_date as start_date',
				'trips.end_date as end_date',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				// DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
				'trips.created_at',
				//DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y") as created_at'),
				'status.name as status'

			)
		// ->whereNotNull('trips.advance_received')
		// ->where('trips.status_id', 3028) //MANAGER APPROVED
		// ->where('trips.status_id', '!=', 3261) //ADVANCE REQUEST APPROVED
			->where('trips.advance_request_approval_status_id', 3260) //NEW
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
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
			->orderBy('trips.status_id', 'desc')
		;

		return Datatables::of($trips)
			->addColumn('checkbox', function ($trip) {
				return '<input id="trip_' . $trip->id . '" type="checkbox" class="check-bottom-layer booking_list " name="booking_list"  value="' . $trip->id . '" data-trip_id="' . $trip->id . '" >
                                                        <label for="trip_' . $trip->id . '"></label>';
			})
			->addColumn('action', function ($trip) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/advance-claim/request/form/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
	}

	public function advanceClaimRequestFormData($trip_id) {

		$trip = Trip::with([
			'advanceRequestPayment',
			'visits',
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'employee.bankDetail',
			'employee.walletDetail',
			'purpose',
			'status',
		])
			->whereNotNull('trips.advance_received')
		// ->where('trips.status_id', 3028) //MANAGER APPROVED
			->where('trips.advance_request_approval_status_id', 3260) //NEW
			->find($trip_id);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		$days = Trip::select(DB::raw('DATEDIFF(end_date,start_date)+1 as days'))->where('id', $trip_id)->first();
		// $trip->start_date = $start_date->start_date;
		// $trip->end_date = $end_date->end_date;
		$trip->days = $days->days + 1;
		$this->data['payment_mode_list'] = $payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$this->data['wallet_mode_list'] = $wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);
		$this->data['trip'] = $trip;
		$this->data['date'] = date('d-m-Y');
		$this->data['success'] = true;
		$this->data['trip_advance_rejection'] = $trip_advance_rejection = Entity::trip_advance_rejection();
		return response()->json($this->data);
	}

	public function saveAdvanceClaimRequest(Request $r) {
		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->advance_request_approval_status_id = 3261;
		$trip->save();

		//PAYMENT SAVE
		$payment = Payment::firstOrNew(['entity_id' => $trip->id]);
		$payment->fill($r->all());
		$payment->payment_of_id = 3250;
		$payment->entity_id = $trip->id;
		$payment->created_by = Auth::user()->id;
		$payment->save();

		//BANK DETAIL SAVE
		if ($r->bank_name) {
			$bank_detail = BankDetail::firstOrNew(['entity_id' => $trip->id]);
			$bank_detail->fill($r->all());
			$bank_detail->detail_of_id = 3243;
			$bank_detail->entity_id = $trip->id;
			$bank_detail->account_type_id = 3243;
			$bank_detail->save();
		}

		//WALLET SAVE
		if ($r->type_id) {
			$wallet_detail = WalletDetail::firstOrNew(['entity_id' => $trip->id]);
			$wallet_detail->fill($r->all());
			$wallet_detail->wallet_of_id = 3243;
			$wallet_detail->entity_id = $trip->id;
			$wallet_detail->save();
		}

		// $trip->visits()->update(['manager_verification_status_id' => 3080]);
		return response()->json(['success' => true]);
	}

	public function approveAdvanceClaimRequest($trip_id) {
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->status_id = 3028;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3081]);
		return response()->json(['success' => true]);
	}

	public function eyatraAdvanceClaimFilterData() {
		$this->data['employee_list'] = Employee::select(DB::raw('CONCAT(employees.code, " / ", users.name) as name'), 'employees.id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)->get();
		$this->data['purpose_list'] = Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get();
		$this->data['trip_status_list'] = Config::select('name', 'id')->where('config_type_id', 501)->get();
		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function rejectAdvanceClaimRequest($trip_id) {
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3022;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3082]);
		return response()->json(['success' => true]);
	}
	public function AdvanceClaimRequestExport(request $request) {
		//dd($request->all());
		DB::beginTransaction();
		try {
			if ($request->export_ids) {
				$trip_ids = explode(',', $request->export_ids);
			} else {
				return back()->with('error', 'Trips not found');
			}
			$trips = Trip::select('users.name', 'employees.code', 'bank_details.account_number', 'bank_details.ifsc_code', 'trips.advance_received', 'trips.id as id')
				->join('employees', 'employees.id', 'trips.employee_id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->leftjoin('bank_details', 'bank_details.entity_id', 'employees.id')
				->whereIn('trips.id', $trip_ids)
				->where('users.user_type_id', 3121)
				->get();

			$trips_status_update = Trip::whereIn('id', $trip_ids)->update(['advance_request_approval_status_id' => 3261]);
			DB::commit();
			//dd($trips);
			$trips_header = ['Employee Name', 'Employee Code', 'Account Number', 'Ifsc code', 'Amount'];
			$logs_details = array();
			if (count($trips) > 0) {
				$count_logs = 1;
				foreach ($trips as $key => $value) {
					if ($value->name) {
						$employee_name = $value->name;
					} else {
						$employee_name = '--';
					}
					if ($value->code) {
						$employee_code = $value->code;
					} else {
						$employee_code = '-';
					}
					if ($value->account_number) {
						$account_number = $value->account_number;
					} else {
						$account_number = '-';
					}
					if ($value->ifsc_code) {
						$ifsc_code = $value->ifsc_code;
					} else {
						$ifsc_code = '-';
					}
					if ($value->advance_received) {
						$advance_received = $value->advance_received;
					} else {
						$advance_received = '-';
					}
					$logs_details[] = [$employee_name, $employee_code, $account_number, $ifsc_code, $advance_received];
					$count_logs++;
					$activity['entity_id'] = $value->id;
					$activity['entity_type'] = 'trip';
					$activity['details'] = "Trip Advance Request Approved";
					$activity['activity'] = "approve";
					$activity_log = ActivityLog::saveLog($activity);
				}
			}

			Excel::create('advance_claim_request_report', function ($excel) use ($trips_header, $logs_details) {
				$excel->sheet('advance_claim_request', function ($sheet) use ($trips_header, $logs_details) {
					$sheet->fromArray($logs_details);
					$sheet->row(1, $trips_header);
				});
			})->export('xls');
			return back();
		} catch (Exception $e) {
			DB::rollBack();
			return back()->with('error', 'Trips advance request have erros');
			//return response()->json(['success' => false, 'errors' => ['Error_Message' => $e->getMessage()]]);
		}
	}

}
