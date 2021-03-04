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
use Excel;
Use Storage;
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

	public function import(Request $request) {

		// dd($request->all());
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '-1');
		$empty_rows = 0;
		$data = $request->all();
		$successCount = 0;
		$errorCount = 0;
		$errors = [];
		$error_str = '';
		$validation = Validator::make($request->all(), ['attachment' => 'required']);
		if ($validation->fails()) {
			$response = ['success' => false, 'errors' => 'Please Upload File'];
			return response()->json($response);
		}
		$attachment = 'attachment';
		$attachment_extension = $request->file($attachment)->getClientOriginalExtension();

		if ($attachment_extension != "xlsx" && $attachment_extension != "xls") {
			$response = ['success' => false, 'error' => 'Cannot Read File, Please Import Excel Format File'];
			return response()->json($response);
		}

		$file = $request->file('attachment')->getRealPath();

		$headers = Excel::selectSheetsByIndex(0)->load($file, function ($reader) {
			$reader->takeRows(1);
		})->toArray();
		// dd($headers);
		$mandatory_fields = [
			'trip_type',
			'trip_id',
			'transaction_number',
			'transaction_date',
			'transaction_amount',
		];

		$missing_fields = [];
		foreach ($mandatory_fields as $mandatory_field) {
			if (!array_key_exists($mandatory_field, $headers[0])) {
				$missing_fields[] = $mandatory_field;
			}
		}
		if (count($missing_fields) > 0) {
			$response = ['success' => false, 'error' => "Invalid File, Mandatory fields are missing.", 'missing_fields' => $missing_fields];
			return response()->json($response);
		}

		$destination = config('custom.outstation_trip_transcation_import_path');

		$timetamp = date('Y_m_d_H_i_s');
		$file_name = $timetamp . '_outstation_import_file.' . $attachment_extension;
		Storage::makeDirectory($destination, 0777);
		$request->file($attachment)->storeAs($destination, $file_name);
		$output_file = $timetamp . '_outstation_import_error_file';

		Excel::create($output_file, function ($excel) use ($headers) {
			$excel->sheet('Import Error Report', function ($sheet) use ($headers) {
				$headings = array_keys($headers[0]);
				$headings[] = 'Error No';
				$headings[] = 'Error Details';
				$sheet->fromArray(array($headings));
			});
		})->store('xlsx', storage_path('app/public/trip/outstation/import/'));

		$total_records = Excel::load(getOutstionTranscationImportExcelPath($file_name), function ($reader) {
			$reader->limitColumns(1);
		})->get();
		$total_records = count($total_records);

		$response = [
			'success' => true,
			'total_records' => $total_records,
			'file' => getOutstionTranscationImportExcelPath($file_name),
			'outputfile' => 'storage/app/public/trip/outstation/import/' . $output_file . '.xlsx',
			'error_report_url' => asset(getOutstionTranscationImportExcelPath($output_file . '.xlsx')),
			'reference' => $timetamp,
			'errorCount' => $errorCount,
			'successCount' => $successCount,
			'errors' => $error_str,
		];

		return response()->json($response);
	}

	public function chunkImport(Request $request) {
		// dd($request->all());
		$error_str = '';
		$errors = array();
		$status_error_msg = array();
		$error_msg = array();
		$error_count = 0;
		$successCount = 0;
		$newCount = 0;
		$updatedCount = 0;
		$records = 0;
		$empty_rows = 0;
		$file = $request->file;
		$total_records = $request->total_records;
		$skip = $request->skip;
		$records = array();
		$output_file = $request->outputfile;
		$request_client_id = $request->client_id;
		$records_per_request = $request->records_per_request;
		$timetamp = $request->reference;

		try {
			$headers = Excel::selectSheetsByIndex(0)->load($file, function ($reader) use ($skip, $records_per_request) {
				$reader->skipRows($skip)->takeRows($records_per_request);
			})->toArray();
		} catch (\Exception $e) {
			$response = ['success' => false, 'error' => $e->getMessage()];
			return response()->json($response);
		}

		// dd($headers);
		$all_error_records = [];
		$errorCount = 0;

		$k = 0;
		$all_error_records = [];

		foreach ($headers as $key => $trip_detail) {
			$original_record = $trip_detail;
			$k = $skip + $k;
			$skip = false;

			$errors = [];

			if (empty($trip_detail['trip_type'])) {
				$errors[] = 'Trip Type Cannot be empty';
				$skip = true;
			}else{
				if(strtolower($trip_detail['trip_type']) != 'outstation'){
					$errors[] = 'Invalid Trip Type - ' . $trip_detail['trip_type'];
					$skip = true;
				}
			}

			if (empty($trip_detail['trip_id'])) {
				$errors[] = 'Trip ID Cannot be empty';
				$skip = true;
			} else {
				$trip = Trip::where('number', $trip_detail['trip_id'])->first();
				if (!$trip) {
					$errors[] = 'Invalid Trip ID - ' . $trip_detail['trip_id'];
					$skip = true;
				}else{
					$employee_claim = EmployeeClaim::where('trip_id', $trip->id)->first();
					if (!$employee_claim) {
						$errors[] = 'Invalid Trip ID - ' . $trip_detail['trip_id'];
						$skip = true;
					}
				}
			}
			
			if (empty($trip_detail['transaction_number'])) {
				$errors[] = 'Transaction Number Cannot be empty';
				$skip = true;
			}else{
				$trip = Trip::where('number', $trip_detail['trip_id'])->first();
				if($trip){
					//Check transcation Number unique
					$payment = Payment::where('payment_of_id',3251)->where('reference_number',$trip_detail['transaction_number'])->first();
					if($payment){
						if($payment->entity_id != $trip->id){
							$errors[] = 'Transaction Number has already been taken- ' . $trip_detail['transaction_number'];
							$skip = true;
						}
					}
				}
			}

			if (empty($trip_detail['transaction_date'])) {
				$errors[] = 'Transaction date Cannot be empty';
				$skip = true;
			}

			if (empty($trip_detail['transaction_amount'])) {
				$errors[] = 'Transaction amount Cannot be empty';
				$skip = true;
			}else{
				if(is_numeric($trip_detail['transaction_amount'])){
					$trip = Trip::where('number', $trip_detail['trip_id'])->first();
					if($trip){
						$employee_claim = EmployeeClaim::where('trip_id', $trip->id)->first();
						if ($employee_claim) {
							$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
							$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
							$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
							$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
							$beta_amount = $employee_claim->beta_amount ? $employee_claim->beta_amount : 0;
							$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount + $beta_amount;

							//Advance amount
							if ($trip->advance_received) {
								if ($trip->advance_received > $total_amount) {
									$errors[] = 'The employee does not return the trip balance amount - ' . $trip_detail['trip_id'];
									$skip = true;
								} else {
									if(($total_amount - $trip->advance_received) != $trip_detail['transaction_amount']){
										$errors[] = 'Transaction amount should be equal to the trip amount - ' . $trip_detail['transaction_amount'];
										$skip = true;
									}
								}
							} else {
								if($total_amount != $trip_detail['transaction_amount']){
									$errors[] = 'Transaction amount should be equal to the trip amount - ' . $trip_detail['transaction_amount'];
									$skip = true;
								}
							}
						}
					}
				}else{
					$errors[] = 'Invalid Transaction amount - ' . $trip_detail['transaction_amount'];
					$skip = true;
				}
			}

			if (!$skip) {

				$trip = Trip::where('number', $trip_detail['trip_id'])->first();

				if($trip){
					$employee_claim = EmployeeClaim::where('trip_id', $trip->id)->first();
					if ($employee_claim) {
					
						$employee_claim->status_id = 3026; //PAID
						$employee_claim->save();

						$trip->status_id = 3026; //PAID
						$trip->save();

						//PAYMENT SAVE
						$payment = Payment::firstOrNew(['payment_of_id' => 3251,'entity_id' => $trip->id]);
						if ($payment->exists) {
							$payment->updated_by = Auth::user()->id;
							$payment->updated_at = Carbon::now();
							$updatedCount++;
						} else {
							$payment->created_by = Auth::user()->id;
							$payment->created_at = Carbon::now();
							$newCount++;
						}
						// $payment->fill($r->all());
						$payment->reference_number = $trip_detail['transaction_number'];
						$payment->amount = $trip_detail['transaction_amount'];
						$payment->payment_mode_id = 3244;
						$payment->date = date('Y-m-d', strtotime($trip_detail['transaction_date']));
						$payment->save();

						$employee_claim->payment_id = $payment->id;
						$employee_claim->claim_approval_datetime = date('Y-m-d H:i:s');
						$employee_claim->save();

						$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
						//Approval Log
						$approval_log = ApprovalLog::saveApprovalLog(3581, $trip->id, 3604, Auth::user()->entity_id, Carbon::now());
						$notification = sendnotification($type = 9, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Paid');
					}
				}
			} else {
				$errorCount++;
				$error_str .= '
                 <div class="mue_errortable_line">
                <span class="mue_ticketerror">Record No:' . ($k + 1) . '</span>
                <span class="mue_rowerror">Reason: ' . implode(',', $errors) . '</span>
                </div>
                    ';
			}

			if (count($errors) > 0) {
				$original_record['Record No'] = $k + 1;
				$original_record['Error Details'] = implode(',', $errors);
				$all_error_records[] = $original_record;
			}
		}

		Excel::load($request->outputfile, function ($excel) use ($all_error_records) {
			$excel->sheet('Import Error Report', function ($sheet) use ($all_error_records) {
				foreach ($all_error_records as $error_record) {
					$sheet->appendRow($error_record, null, 'A1', false, false);
				}
			});
		})->store('xlsx', storage_path('app/public/trip/outstation/import/'));

		$response = ['success' => true, 'processed' => count($headers), 'errors' => $error_str,
			'newCount' => $newCount, 'updatedCount' => $updatedCount, 'errorCount' => $errorCount];
		return response()->json($response);
	}

}
