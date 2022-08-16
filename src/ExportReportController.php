<?php

namespace Uitoux\EYatra;

use App\AxaptaAccountType;
use App\AxaptaBankDetail;
use App\AxaptaExport;
use App\BatchWiseReport;
use App\Config;
use App\CronLog;
use App\Http\Controllers\Controller;
use App\MailConfiguration;
use App\ReportDetail;
use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Mail;
use Redirect;
use Session;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Region;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class ExportReportController extends Controller {
	// Report list filter
	public function getReportListFilter(Request $r) {
		$this->data['type_list'] = Config::select('id', 'name')->where('config_type_id', 538)->get()->prepend(['id' => null, 'name' => 'Select Type']);
		return response()->json($this->data);
	}
	// Report list datas
	public function getReportListData(Request $r) {
		// dd($r->all());
		$report_details = ReportDetail::select(
			'report_details.id',
			'report_details.name',
			'report_details.path',
			DB::raw('DATE_FORMAT(report_details.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
			'configs.name as type'
		)->join('configs', 'configs.id', 'report_details.type_id')
			->where(function ($query) use ($r) {
				if ($r->type_id && $r->type_id != '<%$ctrl.filter_type_id%>') {
					$query->where("report_details.type_id", $r->type_id);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->from_date && $r->from_date != '<%$ctrl.start_date%>') {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("report_details.created_at", '>=', $date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date && $r->to_date != '<%$ctrl.end_date%>') {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("report_details.created_at", '<=', $date);
				}
			})
			->groupBy('report_details.id')
			->orderBy('report_details.created_at', 'desc');

		return Datatables::of($report_details)
			->addColumn('action', function ($report_detail) {
				$download_img = asset('public/img/content/yatra/table/ico-download.svg');
				$download_img_hover = asset('public/img/content/yatra/table/ico-download-hover.svg');
				return '<a href="' . url('/' . $report_detail->path) . '" download>
                    <img src="' . $download_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $download_img_hover . '" onmouseout=this.src="' . $download_img . '" >
                </a>';
			})
			->make(true);
	}
	// GST Report generate form date
	public function getForm(Request $r) {
		$this->data['base_url'] = URL::to('/');
		$this->data['token'] = csrf_token();
		$this->data['business_list'] = Business::select('id', 'name')->get()->prepend(['id' => -1, 'name' => 'All Businesses']);
		$this->data['region_list'] = Region::select('id', 'name')->get()->prepend(['id' => -1, 'name' => 'All Regions']);
		return response()->json($this->data);
	}
	public function getView(Request $r) {
		$this->data['base_url'] = URL::to('/');
		$this->data['token'] = csrf_token();
		$this->data['business_list'] = Business::select('id', 'name')->get()->prepend(['id' => -1, 'name' => 'All Businesses']);
		return response()->json($this->data);
	}
	// Taking outlet based on region
	public function getOutlet($region_ids = '') {
		$region_ids = explode(',', $region_ids);

		$this->data['outlet_list'] = Outlet::withTrashed()
			->leftJoin('ey_addresses as a', function ($join) {
				$join->on('a.entity_id', '=', 'outlets.id')
					->where('a.address_of_id', 3160);
			})
			->join('ncities as city', 'city.id', 'a.city_id')
			->join('nstates as s', 's.id', 'city.state_id')
			->join('regions as r', 'r.state_id', 's.id')
			->whereIn('r.id', $region_ids)
			->select('outlets.id', 'outlets.name')
			->get()
			->prepend(['id' => -1, 'name' => 'All Outlets']);
		return response()->json($this->data);
	}

	public function cronGenerateTripReport($cronLogId) {
		//CRON LOG SAVE
		$cronLog = CronLog::firstOrNew([
			'id' => $cronLogId,
		]);
		$cronLog->command = "generate:trip-ax-report";
		$cronLog->status = "Inprogress";
		$cronLog->created_at = Carbon::now();
		$cronLog->save();

		try {
			$axaptaAccountTypes = AxaptaAccountType::select([
				'name',
				'code',
			])
				->get();

			$axaptaBankDetails = AxaptaBankDetail::select([
				'name',
				'code',
			])
				->get();

			$agentTripVisits = Visit::select([
				'visits.id as visitId',
				'visits.trip_id as tripId',
				'agents.code as agentCode',
				'visit_bookings.gstin as enteredGstin',
				'visit_bookings.invoice_number as invoiceNumber',
				'visit_bookings.invoice_date as invoiceDate',
				DB::raw('SUM(visit_bookings.total + visit_bookings.agent_total) as totalAmount'),
				DB::raw('SUM(visit_bookings.amount) as ticketAmount'),
				DB::raw('SUM(visit_bookings.tax_percentage) as ticketPercentage'),
				DB::raw('SUM(visit_bookings.cgst) as ticketCgst'),
				DB::raw('SUM(visit_bookings.sgst) as ticketSgst'),
				DB::raw('SUM(visit_bookings.igst) as ticketIgst'),
				DB::raw('SUM(visit_bookings.agent_service_charges) as agentServiceCharges'),
				DB::raw('SUM(visit_bookings.agent_tax_percentage) as agentPercentage'),
				DB::raw('SUM(visit_bookings.agent_cgst) as agentCgst'),
				DB::raw('SUM(visit_bookings.agent_sgst) as agentSgst'),
				DB::raw('SUM(visit_bookings.agent_igst) as agentIgst'),
				DB::raw('COALESCE(outlets.axapta_location_id, "") as axaptaLocationId'),
			])
				->join('agents', 'agents.id', 'visits.agent_id')
				->leftjoin('outlets', 'outlets.id', 'agents.outlet_id')
				->join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
				->where('visits.booking_method_id', 3042) //AGENT
				->where('visits.agent_ax_export_synched', 0) //NOT SYNCHED
				->groupBy('visits.id')
				->get();

			dd($agentTripVisits);
			$exceptionErrors = [];
			if ($agentTripVisits->isNotEmpty()) {
				foreach ($agentTripVisits as $key => $agentTripVisit) {
					DB::beginTransaction();
					try {
						$trip = Trip::with([
							'employee',
							'employee.outlet',
							'employee.outlet.address',
							'employee.outlet.address.city',
							'employee.outlet.address.city.state',
							'employee.user',
							'purpose',
						])
							->find($agentTripVisit->tripId);
						if ($trip) {
							//TOTAL AMOUNT ENTRY
							$this->agentAxaptaExportProcess(1, $agentTripVisit, $trip, $axaptaAccountTypes, $axaptaBankDetails);

							//TICKET ENTRIES
							$this->agentAxaptaExportProcess(2, $agentTripVisit, $trip, $axaptaAccountTypes, $axaptaBankDetails);

							//AGENT SERVICE CHARGE ENTRIES
							$this->agentAxaptaExportProcess(3, $agentTripVisit, $trip, $axaptaAccountTypes, $axaptaBankDetails);

							//BANK DEBIT ENTRY
							$this->agentAxaptaExportProcess(4, $agentTripVisit, $trip, $axaptaAccountTypes, $axaptaBankDetails);

							//BANK CREDIT ENTRY
							$this->agentAxaptaExportProcess(5, $agentTripVisit, $trip, $axaptaAccountTypes, $axaptaBankDetails);

							//AX SYNCHED
							Visit::where('id', $agentTripVisit->visitId)->update([
								'agent_ax_export_synched' => 1,
							]);

							DB::commit();
							continue;
						} else {
							$exceptionErrors[] = "Trip ID ( " . $agentTripVisit->tripId . " ) : not found";
							DB::rollBack();
							continue;
						}
					} catch (\Exception $e) {
						DB::rollBack();
						$exceptionErrors[] = "Trip ID ( " . $agentTripVisit->tripId . " ) : " . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
						continue;
					}
				}
			}

			$cronLog->status = "Completed";
			if (!empty($exceptionErrors)) {
				$cronLog->errors = json_encode($exceptionErrors);
			}
			$cronLog->updated_at = Carbon::now();
			$cronLog->save();
		} catch (\Exception $e) {
			//CRON LOG SAVE
			$cronLog->status = "Failed";
			$cronLog->errors = $e;
			$cronLog->updated_at = Carbon::now();
			$cronLog->save();
		}
	}

	public function agentAxaptaExportProcess($type, $agentTripVisit, $trip, $axaptaAccountTypes, $axaptaBankDetails) {
		$employeeCode = $trip->employee ? $trip->employee->code : '';
		$employeeName = $trip->employee ? $trip->employee->name : '';
		$purpose = $trip->purpose ? $trip->purpose->name : '';
		$txt = '';

		//TOTAL AMOUNT ENTRY
		if ($type == 1) {

			$transactionDate = date('Y-m-d', strtotime($trip->created_at));
			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Vendor')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "V", $transactionDate, $accountType, $agentTripVisit->agentCode, "COMMON-MDS", $txt, 0.00, $agentTripVisit->totalAmount, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

		} elseif ($type == 2) {
			//TICKET ENTRIES

			$transactionDate = date('Y-m-d', strtotime($trip->created_at));
			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Ledger')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			//TICKET TAXABLE AMOUNT ENTRY
			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, "4572-COMMON-MDS", "COMMON-MDS", $txt, $agentTripVisit->ticketAmount, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

			//GST SPLITUPS
			if ($trip->employee && $trip->employee->outlet && $trip->employee->outlet->address && $trip->employee->outlet->address->city && $trip->employee->outlet->address->city->state) {
				$employeeGstCode = !empty($trip->employee->outlet->address->city->state->gstin_state_code) ? $trip->employee->outlet->address->city->state->gstin_state_code : '';

				if (!empty($employeeGstCode) && !empty($agentTripVisit->enteredGstin)) {
					$enteredGstinCode = substr($agentTripVisit->enteredGstin, 0, 2);
					$enteredGstinState = Nstate::where('gstin_state_code', $enteredGstinCode)->first();

					if ($enteredGstinState) {
						//INTRA STATE (CGST AND SGST)
						if ($enteredGstinCode == $employeeGstCode) {
							$cgstPercentage = $sgstPercentage = ($agentTripVisit->ticketPercentage) / 2;
							$cgstEntryTxt = '';
							$sgstEntryTxt = '';
							$cgstLedgerDimension = $enteredGstinState->axapta_cgst_code . "-COMMON-MDS";
							$sgstLedgerDimension = $enteredGstinState->axapta_sgst_code . "-COMMON-MDS";

							if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
								$cgstEntryTxt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose . " - CGST - " . $cgstPercentage . "%";
							}
							if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
								$sgstEntryTxt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose . " - SGST - " . $sgstPercentage . "%";
							}

							//CGST ENTRY
							$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, $cgstLedgerDimension, "COMMON-MDS", $cgstEntryTxt, $agentTripVisit->ticketCgst, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

							//SGST ENTRY
							$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, $sgstLedgerDimension, "COMMON-MDS", $sgstEntryTxt, $agentTripVisit->ticketSgst, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

						} else {
							//INTER STATE (IGST)
							$igstPercentage = $agentTripVisit->ticketPercentage;
							$igstEntryTxt = '';
							$igstLedgerDimension = $enteredGstinState->axapta_igst_code . "-COMMON-MDS";

							if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
								$igstEntryTxt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose . " - IGST - " . $igstPercentage . "%";
							}

							$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, $igstLedgerDimension, "COMMON-MDS", $txt, $agentTripVisit->ticketIgst, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);
						}
					}
				}
			}

		} elseif ($type == 3) {
			//AGENT SERVICE CHARGE ENTRIES

			$transactionDate = date('Y-m-d', strtotime($trip->created_at));
			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Ledger')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			//AGENT SERVICE CHARGE ENTRY
			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, "4572-COMMON-MDS", "COMMON-MDS", $txt, $agentTripVisit->agentServiceCharges, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

			//GST SPLITUPS
			if (!empty($agentTripVisit->enteredGstin)) {
				$enteredGstinCode = substr($agentTripVisit->enteredGstin, 0, 2);
				$enteredGstinState = Nstate::where('gstin_state_code', $enteredGstinCode)->first();

				if ($enteredGstinState) {
					//INTRA STATE (CGST AND SGST)
					$cgstPercentage = $sgstPercentage = ($agentTripVisit->agentPercentage) / 2;
					$cgstEntryTxt = '';
					$sgstEntryTxt = '';
					$cgstLedgerDimension = $enteredGstinState->axapta_cgst_code . "-COMMON-MDS";
					$sgstLedgerDimension = $enteredGstinState->axapta_sgst_code . "-COMMON-MDS";

					if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
						$cgstEntryTxt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose . " - CGST - " . $cgstPercentage . "%";
					}
					if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
						$sgstEntryTxt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose . " - SGST - " . $sgstPercentage . "%";
					}

					//CGST ENTRY
					$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, $cgstLedgerDimension, "COMMON-MDS", $cgstEntryTxt, $agentTripVisit->agentCgst, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

					//SGST ENTRY
					$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, $sgstLedgerDimension, "COMMON-MDS", $sgstEntryTxt, $agentTripVisit->agentSgst, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

				}
			}

		} elseif ($type == 4) {
			//BANK DEBIT ENTRY

			$transactionDate = date('Y-m-d', strtotime($trip->created_at));
			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Vendor')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			//BANK DEBIT ENTRY
			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_CHQ", "V", $transactionDate, $accountType, $agentTripVisit->agentCode, "COMMON-MDS", $txt, $agentTripVisit->totalAmount, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

		} elseif ($type == 5) {
			//BANK CREDIT ENTRY

			$transactionDate = date('Y-m-d', strtotime($trip->created_at));
			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Bank')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			$axaptaBankDetail = $axaptaBankDetails->where('name', 'HDFC Bank')->first();
			$ledgerDimension = $axaptaBankDetail ? $axaptaBankDetail->code : '';

			//BANK CREDIT ENTRY
			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_CHQ", "D", $transactionDate, $accountType, $ledgerDimension, "COMMON-MDS", $txt, 0.00, $agentTripVisit->totalAmount, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

		}
	}

	public function saveAxaptaExport($companyId, $entityTypeId, $entityId, $journalName, $voucher, $transactionDate, $accountType, $ledgerDimension, $defaultDimension, $txt, $amountCurDebit, $amountCurCredit, $invoiceNumber, $invoiceDate, $axaptaLocationId) {
		$data = array(
			'company_id' => $companyId,
			'entity_type_id' => $entityTypeId,
			'entity_id' => $entityId,
			'CurrencyCode' => "INR",
			'JournalName' => $journalName,
			'Voucher' => $voucher,
			'ApproverPersonnelNumber' => "INTG01",
			'Approved' => 1,
			'TransDate' => $transactionDate,
			'AccountType' => $accountType,
			'LedgerDimension' => $ledgerDimension,
			'DefaultDimension' => $defaultDimension,
			'Txt' => $txt,
			'AmountCurDebit' => $amountCurDebit,
			'AmountCurCredit' => $amountCurCredit,
			'OffsetDefaultDimension' => $defaultDimension,
			'PaymMode' => 'ENET',
			'Invoice' => $invoiceNumber,
			'DocumentNum' => $invoiceNumber,
			'DocumentDate' => $invoiceDate,
			'LogisticsLocation_LocationId' => $axaptaLocationId,
		);
		$axaptaExport = new AxaptaExport;
		$axaptaExport->fill($data);
		$axaptaExport->save();

		//UPDATE LINENUM
		$axaptaExport->LineNum = $axaptaExport->id;
		$axaptaExport->save();
	}

	// Bank statement report
	public function bankStatement(Request $r) {
		$business_ids = explode(',', $r->business_ids);
		//dd($r);
		$time_stamp = date('Y_m_d_h_i_s');
		foreach ($business_ids as $business_id) {
			$outstations = Employee::select(
				'employees.code as Account_Number',
				'u.name as Name',
				'bd.account_number as Bank_Account_Number',
				't.description as Purpose',
				't.created_at as Created_Date_and_Time',
				't.company_id as company_id',
				't.claimed_date as documentdate',
				't.id as invoice',
				'eyec.balance_amount as Amount',
				't.number as documentnum',
				'eyec_s.name as ledgerdiamension',
				's.name as sbuname'
			)
				->join('users as u', 'u.entity_id', 'employees.id')
				->join('bank_details as bd', 'bd.entity_id', 'employees.id')
				->join('trips as t', 't.employee_id', 'employees.id')
				->join('ey_employee_claims as eyec', 'eyec.employee_id', 'employees.id')
				->leftjoin('outlets as ol', 'ol.id', 't.outlet_id')
				->join('sbus as s', 's.id', 'employees.sbu_id')
				->leftjoin('sbus as eyec_s', 'eyec_s.id', 'eyec.sbu_id')
				->join('departments', 'departments.id', 'employees.department_id')
				->join('businesses', 'businesses.id', 'departments.business_id')
				->where('t.status_id', 3026)
				->where('eyec.status_id', 3026)
				->where('eyec.amount_to_pay', 1)
				->where('eyec.batch', 0)
				->where('t.batch', 0)
				->where('departments.business_id', '=', $business_id)
				->groupBy('eyec.id')
				->get()->toArray();

			//dd($outstations);
			$advance_amount = Employee::select(
				'employees.code as Account_Number',
				'u.name as Name',
				'bd.account_number as Bank_Account_Number',
				't.description as Purpose',
				't.created_at as Created_Date_and_Time',
				't.company_id as company_id',
				't.created_at as documentdate',
				't.id as invoice',
				't.advance_received as Amount',
				't.number as documentnum',
				DB::raw('COALESCE(eyec_s.name, "") as ledgerdiamension'),
				DB::raw('COALESCE(s.name, "") as sbuname')
			)
				->join('users as u', 'u.entity_id', 'employees.id')
				->join('bank_details as bd', 'bd.entity_id', 'employees.id')
				->join('trips as t', 't.employee_id', 'employees.id')
				->join('ey_employee_claims as eyec', 'eyec.employee_id', 'employees.id')
				->leftjoin('outlets as ol', 'ol.id', 't.outlet_id')
				->join('sbus as s', 's.id', 'employees.sbu_id')
				->leftjoin('sbus as eyec_s', 'eyec_s.id', 'eyec.sbu_id')
				->join('departments', 'departments.id', 'employees.department_id')
				->join('businesses', 'businesses.id', 'departments.business_id')
				->where('t.status_id', 3028)
				->where('t.advance_received', '>', 0)
				->where('t.batch', 0)
				->where('departments.business_id', '=', $business_id)
				->groupBy('t.id')
				->get()->toArray();
			//dd($advance_amount);
			$claims = Employee::select(
				'employees.code as Account_Number',
				'u.name as Name',
				'bd.account_number as Bank_Account_Number',
				'lt.description as Purpose',
				'lt.created_at as Created_Date_and_Time',
				'lt.company_id as company_id',
				'lt.claim_amount as Amount',
				'lt.id as invoice',
				'lt.claimed_date as documentdate',
				'lt.number as documentnum',
				'lt_s.name as ledgerdiamension',
				's.name as sbuname'
			)
				->join('users as u', 'u.entity_id', 'employees.id')
				->join('bank_details as bd', 'bd.entity_id', 'employees.id')
				->join('local_trips as lt', 'lt.employee_id', 'employees.id')
				->leftjoin('outlets as ol', 'ol.id', 'lt.outlet_id')
				->leftjoin('sbus as s', 's.id', 'employees.sbu_id')
				->leftjoin('sbus as lt_s', 'lt_s.id', 'lt.sbu_id')
				->join('departments', 'departments.id', 'employees.department_id')
				->join('businesses', 'businesses.id', 'departments.business_id')
				->where('lt.status_id', '=', '3026')
				->where('lt.batch', 0)
				->where('departments.business_id', '=', $business_id)
				->groupBy('lt.id')
				->get()->toArray();
			$locals = array_merge($claims, $outstations, $advance_amount);
			if (count($locals) == 0) {
				continue;
			}
			//dd($locals);
			$batch_id = BatchWiseReport::where('date', '=', date('Y-m-d'))->orderBy('id', 'DESC')->pluck('name')->first();
			$batch = ((int) $batch_id ?: '0') + 1;
			$local_trips_header = [
				'SNo',
				'Account Number',
				'Name',
				'Bank Account Number',
				'Purpose',
				'Created Date and Time',
				'Amount',
				'Posted',
				'Batch',
				'Bank Date',
			];
			$travelex_header = [
				'LINENUM',
				'INVOICE',
				'DOCUMENTNUM',
				'DOCUMENTDATE',
				'LEDGERDIMENSION',
				'TRANSDATE',
				'ACCOUNTTYPE',
				'AMOUNTCURCREDIT',
				'OFFSETACCOUNTTYPE',
				'AMOUNTCURDEBIT',
				'TXT',
				'VOUCHER',
				'POSTED',
				'DATAAREAID',
				'JOURNALTYPE',
				'JOURNALNAME',
				'SOPROSTATUS',
				'ACCOUNTNUM',
				'COMPANY',
				'DEFAULTLEDGERDIAMENSIONTXT',
				'OFFSETLEDGERDIAMENSION',
				'PERSONNELNUMBER',
				'LOGISTICSLOCATION',
			];
			if (count($locals) > 0) {
				$local_trips = array();
				$travelex_details = array();
				$voucher = 'v';
				$dataareaid = 'tvs';
				$journaltype = '0';
				$journalname = 'TLXCHQ';
				$soprostatus = '2';
				$company = 'tvs';
				$offsetledgerdiamension = '0';
				$personnelnumber = 'INTG01';
				$logisticlocation = 'T V Sundram Iyengar & Sons P Ltd(CEN)';
				$transdate = $time_stamp;
				$account_type = '2';
				$account_type_two = '0';
				$offsetaccounttype = '0';
				$offsetaccounttype_two = '2';
				$amountcurcredit = '00.00';
				//$amountcurcredit_two=$local['Amount'];
				//$amountcurdebit=$local['Amount'];
				$amountcurdebit_two = '0.00';
				$posted = '0';

				$total_amount = 0;
				$s_no = 1;
				$l_no = 1;
				foreach ($locals as $key => $local) {
					$total_amount += $local['Amount'];
					$local_trip = [
						$l_no++,
						'EMP_' . $local['Account_Number'],
						$local['Name'],
						$local['Bank_Account_Number'],
						'(' . $local['Account_Number'] . '-' . $local['Name'] . ')' . '-' . $local['Purpose'],
						$local['Created_Date_and_Time'],
						$local['Amount'],
						$posted,
						$batch,
						$time_stamp,
					];
					$travelex_local = [
						$s_no++,
						'AC-' . $local['invoice'],
						$local['documentnum'],
						$local['documentdate'],
						'AL-' . $local['ledgerdiamension'],
						$transdate,
						$account_type,
						$amountcurcredit,
						$offsetaccounttype,
						$local['Amount'],
						'(' . $local['Account_Number'] . '-' . $local['Name'] . ')' . '-' . $local['Purpose'],
						$voucher = 'v',
						$posted = '0',
						$dataareaid = 'tvs',
						$journaltype = '0',
						$journalname = 'TLXCHQ',
						$soprostatus = '2',
						'EMP_' . $local['Account_Number'],
						$company = 'tvs',
						$local['sbuname'] . '-' . $local['ledgerdiamension'],
						$offsetledgerdiamension = '0',
						$personnelnumber = 'INTG01',
						$logisticlocation = 'T V Sundram Iyengar & Sons P Ltd(CEN)',
					];
					$travelex_detail = [
						$s_no++,
						'AC-' . $local['invoice'],
						$local['documentnum'],
						$local['documentdate'],
						'AL-' . $local['ledgerdiamension'],
						$transdate,
						$account_type_two,
						$local['Amount'],
						$offsetaccounttype_two,
						$amountcurdebit_two,
						'(' . $local['Account_Number'] . '-' . $local['Name'] . ')' . '-' . $local['Purpose'],
						$voucher = 'v',
						$posted = '0',
						$dataareaid = 'tvs',
						$journaltype = '0',
						$journalname = 'TLXCHQ',
						$soprostatus = '2',
						'1215-' . $local['ledgerdiamension'] . '-' . $local['sbuname'],
						$company = 'tvs',
						$local['sbuname'] . '-' . $local['ledgerdiamension'],
						$offsetledgerdiamension = '0',
						$personnelnumber = 'INTG01',
						$logisticlocation = 'T V Sundram Iyengar & Sons P Ltd(CEN)',
					];
					$local_trips[] = $local_trip;
					$travelex_details[] = $travelex_local;
					$travelex_details[] = $travelex_detail;
				}
			} else {
				Session()->flash('error', 'No Data Found');
				// return Redirect::to('/#!/report/list');
			}

			$consolidation_local = [
				$s_no++,
				'CC-' . $local['invoice'],
				'0001',
				$local['documentdate'],
				'AL-' . $local['ledgerdiamension'],
				$transdate,
				'6',
				$total_amount,
				'2',
				'0.00',
				'consolidation',
				$voucher = 'v',
				$posted = '0',
				$dataareaid = 'tvs',
				$journaltype = '0',
				$journalname = 'TLXCHQ',
				$soprostatus = '2',
				'TVS-044',
				$company = 'tvs',
				'F&A-TVM',
				$offsetledgerdiamension = '0',
				$personnelnumber = 'INTG01',
				$logisticlocation = 'T V Sundram Iyengar & Sons P Ltd(CEN)',
			];
			$consolidation_detail = [
				$s_no++,
				'CC-' . $local['invoice'],
				'0001',
				$local['documentdate'],
				'AL-' . $local['ledgerdiamension'],
				$transdate,
				'0',
				'0.00',
				'0',
				$total_amount,
				'consolidation',
				$voucher = 'v',
				$posted = '0',
				$dataareaid = 'tvs',
				$journaltype = '0',
				$journalname = 'TLXCHQ',
				$soprostatus = '2',
				'1215-TVM-F&A',
				$company = 'tvs',
				'F&A-TVM',
				$offsetledgerdiamension = '0',
				$personnelnumber = 'INTG01',
				$logisticlocation = 'T V Sundram Iyengar & Sons P Ltd(CEN)',
			];
			$travelex_details[] = $consolidation_local;
			$travelex_details[] = $consolidation_detail;
			// ob_end_clean();
			// ob_start();
			$business_name = Business::where('id', '=', $business_id)->pluck('name')->first();
			//dd($business_name);
			$outputfile = $business_name . '_travelex_report_' . $time_stamp;
			$file = Excel::create($outputfile, function ($excel) use ($travelex_header, $travelex_details) {
				$excel->sheet('travelex_', function ($sheet) use ($travelex_header, $travelex_details) {
					$sheet->fromArray($travelex_details, NULL, 'A1');
					$sheet->row(1, $travelex_header);
					$sheet->row(1, function ($row) {
						$row->setBackground('#07c63a');
					});
				});
			})->store('xlsx', storage_path('app/public/travelex_report/'));
			//dd($file);
			//SAVE TRAVELEX REPORTS
			$report_details = new ReportDetail;
			$report_details->company_id = $local['company_id'];
			$report_details->type_id = 3722;
			$report_details->name = $file->filename;
			$report_details->path = 'storage/app/public/travelex_report/' . $outputfile . '.xlsx';
			$report_details->batch = $batch;
			$report_details->no_of_credits = $s_no;
			$report_details->bank_date = $time_stamp;
			$report_details->credit_total_amount = $total_amount;
			$report_details->save();
			$batch_wise_reports = new BatchWiseReport;
			$batch_wise_reports->report_detail_id = $report_details->id;
			$batch_wise_reports->name = $report_details->batch;
			$batch_wise_reports->date = $time_stamp;
			$batch_wise_reports->save();
			$outputfile_bank = $business_name . '_bank_statement_' . $time_stamp;
			$file_one = Excel::create($outputfile_bank, function ($excel) use ($local_trips_header, $local_trips) {
				$excel->sheet('Bank Statement', function ($sheet) use ($local_trips_header, $local_trips) {
					$sheet->fromArray($local_trips, NULL, 'A1');
					$sheet->row(1, $local_trips_header);
					$sheet->row(1, function ($row) {
						$row->setBackground('#07c63a');
					});
				});
			})->save('xlsx', storage_path('app/public/bank_statement_report/'));
			//dd($file_one);

			//SAVE BANK STATEMENT REPORTS
			$report_details = new ReportDetail;
			$report_details->company_id = $local['company_id'];
			$report_details->type_id = 3721;
			$report_details->name = $file_one->filename;
			$report_details->path = 'storage/app/public/bank_statement_report/' . $outputfile_bank . '.xlsx';
			$report_details->batch = $batch;
			$report_details->no_of_credits = $l_no;
			$report_details->bank_date = $time_stamp;
			$report_details->credit_total_amount = $total_amount;
			$report_details->save();
			$batch_wise_reports = new BatchWiseReport;
			$batch_wise_reports->report_detail_id = $report_details->id;
			$batch_wise_reports->name = $report_details->batch;
			$batch_wise_reports->date = $time_stamp;
			$batch_wise_reports->save();

			foreach ($locals as $local) {
				$batch_update = DB::table('trips')->where('id', $local['invoice'])->where('status_id', '=', '3028')->where('batch', '0')->update(['batch' => 1]);

				$batch_update = DB::table('trips')->where('id', $local['invoice'])->where('status_id', '=', '3026')->where('batch', '0')->update(['batch' => 1]);
				$batch_update = DB::table('ey_employee_claims')->where('trip_id', $local['invoice'])->where('status_id', '=', '3026')->where('batch', '0')->update(['batch' => 1]);

				$batch_update = DB::table('local_trips')->where('id', $local['invoice'])->where('status_id', '=', '3026')->where('batch', '0')->update(['batch' => 1]);
			}
		}
		return Redirect::to('/#!/report/list');
	}

	// Travel X to Ax report
	public function travelXtoAx(Request $r) {

	}
	// Gst report
	public function gst(Request $r) {
		//dd($r->business_ids);
		ob_end_clean();
		$date = explode(' to ', $r->period);
		$from_date = date('Y-m-d', strtotime($date[0]));
		$to_date = date('Y-m-d', strtotime($date[1]));
		$region_ids = $outlet_ids = [];
		if ($r->regions) {
			if (in_array('-1', json_decode($r->regions))) {
				$region_ids = Outlet::pluck('id')->toArray();
			} else {
				$region_ids = json_decode($r->regions);
			}
		}
		if ($r->outlets) {
			if (in_array('-1', json_decode($r->outlets))) {
				$outlet_ids = Outlet::pluck('id')->toArray();
			} else {
				$outlet_ids = json_decode($r->outlets);
			}
		}
		// dd($region_ids, $outlet_ids);
		ini_set('max_execution_time', 0);
		$excel_headers = [
			'LINENUM',
			'EMPLOYEE CODE',
			'EMPLOYEE NAME',
			'OUTLET',
			'SBU',
			'CLAIM NUMBER',
			'CLAIM DATE',
			'INVOICE NUMBER',
			'TRANSPORT GSTIN',
			'TRANSPORT AMOUNT',
			'TRANSPORT TAX',
			'LODGING GST NUMBER',
			'SUPPLIER NAME',
			'INVOICE AMOUNT',
			'TAX PERCENTAGE',
			'CGST AMOUNT',
			'SGST AMOUNT',
			'IGST AMOUNT',
			// 'ADDRESS',
			'DATE',
		];
		$gst_details = EmployeeClaim::select(
			DB::raw('COALESCE(employees.code, "") as emp_code'),
			DB::raw('COALESCE(users.name, "") as emp_name'),
			DB::raw('COALESCE(outlets.code, "") as outlet'),
			DB::raw('COALESCE(sbus.name, "") as sbu'),
			DB::raw('COALESCE(ey_employee_claims.number, "") as claim_number'),
			DB::raw('COALESCE(DATE_FORMAT(ey_employee_claims.created_at,"%d-%m-%Y"), "") as claim_date'),
			DB::raw('COALESCE(lodgings.gstin, "") as gst_number'),
			DB::raw('COALESCE(lodgings.lodge_name, "") as supplier_name'),
			DB::raw('COALESCE(visit_bookings.gstin, "") as transport_gst_number'),
			DB::raw('COALESCE(trips.number, "") as invoice_number'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.amount, 0)),2,"en_IN") as transport_amount'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.tax, 0)),2,"en_IN") as tax'),
			DB::raw('format(ROUND(IFNULL(lodgings.amount, 0)),2,"en_IN") as invoice_amount'),
			DB::raw('format(ROUND(IFNULL(lodgings.tax_percentage, 0)),2,"en_IN") as tax_percentage'),
			DB::raw('format(ROUND(IFNULL(lodgings.cgst, 0)),2,"en_IN") as cgst'),
			DB::raw('format(ROUND(IFNULL(lodgings.sgst, 0)),2,"en_IN") as sgst'),
			DB::raw('format(ROUND(IFNULL(lodgings.igst, 0)),2,"en_IN") as igst'),
			DB::raw('COALESCE(DATE_FORMAT(ey_employee_claims.created_at,"%d-%m-%Y"), "") as date')
		)->leftJoin('employees', 'employees.id', 'ey_employee_claims.employee_id')
			->leftJoin('sbus', 'sbus.id', 'employees.sbu_id')
			->leftJoin('users', function ($user_q) {
				$user_q->on('employees.id', 'users.entity_id')
					->where('users.user_type_id', 3121);
			})
			->leftJoin('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->leftJoin('visits', 'visits.trip_id', 'trips.id')
			->leftJoin('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
			->leftJoin('lodgings', 'lodgings.trip_id', 'trips.id')
			->leftJoin('outlets', 'outlets.id', 'trips.outlet_id')
			->leftJoin('ey_addresses as a', function ($join) {
				$join->on('a.entity_id', '=', 'outlets.id')
					->where('a.address_of_id', 3160);
			})
			->leftJoin('ncities as city', 'city.id', 'a.city_id')
			->leftJoin('nstates as s', 's.id', 'city.state_id')
			->leftJoin('regions as r', 'r.state_id', 's.id')
			->join('departments', 'departments.id', 'employees.department_id')
			->join('businesses', 'businesses.id', 'departments.business_id')
			->where(function ($q) use ($region_ids, $outlet_ids) {
				if (count($outlet_ids) == 0) {
					$q->whereIn('r.id', $region_ids);
				} else {
					$q->whereIn('outlets.id', $outlet_ids);
				}
			})
			->where('ey_employee_claims.status_id', 3026)
			->whereDate('ey_employee_claims.created_at', '>=', $from_date)
			->whereDate('ey_employee_claims.created_at', '<=', $to_date)
			->where('departments.business_id', '=', $r->business_ids)
			->groupBy('ey_employee_claims.id')
			->get();
		// dd(count($gst_details));
		if (count($gst_details) == 0) {
			Session()->flash('error', 'No Data Found');
			//return redirect()->to('/#!/gst/report');
		}
		$export_details = [];
		$s_no = 1;
		foreach ($gst_details as $gst_detail_key => $gst_detail) {
			$export_data = [
				$s_no++,
				$gst_detail->emp_code,
				$gst_detail->emp_name,
				$gst_detail->outlet,
				$gst_detail->sbu,
				$gst_detail->claim_number,
				$gst_detail->claim_date,
				$gst_detail->invoice_number,
				$gst_detail->transport_gst_number,
				$gst_detail->transport_amount,
				$gst_detail->tax,
				$gst_detail->gst_number,
				$gst_detail->supplier_name,
				$gst_detail->invoice_amount,
				$gst_detail->tax_percentage,
				$gst_detail->cgst,
				$gst_detail->sgst,
				$gst_detail->igst,
				$gst_detail->date,
			];

			$export_details[] = $export_data;
		}
		$title = 'GST_REPORT_' . Carbon::now();
		$sheet_name = 'GST REPORT';
		Excel::create($title, function ($excel) use ($export_details, $excel_headers, $sheet_name) {
			$excel->sheet($sheet_name, function ($sheet) use ($export_details, $excel_headers) {
				$sheet->fromArray($export_details, NULL, 'A1');
				$sheet->row(1, $excel_headers);
				$sheet->row(1, function ($row) {
					$row->setBackground('#c4c4c4');
				});
			});
			$excel->setActiveSheetIndex(0);
		})->download('xlsx');
	}
	// Send mail
	public function sendMail() {
		try {
			$current_date = date('Y-m-d');
			$mail_config_id = 3731;
			$mail_config_details = MailConfiguration::select(
				'company_id',
				'to_email',
				'cc_email'
			)->where('config_id', $mail_config_id)
				->get();
			foreach ($mail_config_details as $key => $mail_config_detail) {
				$to_email = explode(',', $mail_config_detail->to_email);
				$cc_email = explode(',', $mail_config_detail->cc_email);

				$mail_attachements = ReportDetail::whereIn('type_id', [3721, 3722])
					->whereDate('created_at', $current_date)
					->where('company_id', $mail_config_detail->company_id)
					->pluck('path')
					->toArray();

				$content = 'Kindly find your Bank Statement And Travek X to Ax Report Below.';
				if (count($mail_attachements) == 0) {
					$content = 'No reports found today.';
				}

				$subject = 'Mail Report';
				$arr['content'] = $content;
				$arr['subject'] = $subject;
				$arr['to_email'] = $to_email;
				$arr['cc_email'] = $cc_email;
				$arr['base_url'] = URL::to('/');

				// return view('/mail/report_mail', $arr);

				$view_name = 'mail.report_mail';
				Mail::send(['html' => $view_name], $arr, function ($message) use ($subject, $cc_email, $to_email, $mail_attachements) {
					$message->to($to_email)->subject($subject);
					$message->cc($cc_email)->subject($subject);
					$message->from('travelex@tvs.in');
					if (count($mail_attachements) > 0) {
						foreach ($mail_attachements as $file) {
							if ($file) {
								$filePath = storage_path(str_replace('storage/', '', $file));
								$message->attach($filePath);
							}
						}
					}
				});
			}
			return redirect('/')->with('success', 'Mail Sent');
		} catch (\Exception $e) {
			$error = 'Error : ' . $e->getMessage() . ' - Line Number : ' . $e->getLine();
			\Log::info($error);
			return redirect('/')->with('error', $error);
		}
	}
}
