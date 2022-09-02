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

	public function cronGenerateAgentAxReport($cronLogId) {
		//CRON LOG SAVE
		$cronLog = CronLog::firstOrNew([
			'id' => $cronLogId,
		]);
		$cronLog->command = "generate:agent-ax-report";
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
				DB::raw('DATE_FORMAT(visit_bookings.invoice_date,"%Y-%m-%d") as invoiceDate'),
				DB::raw('SUM(COALESCE(visit_bookings.total, 0) + COALESCE(visit_bookings.agent_total, 0)) as totalAmount'),
				DB::raw('SUM(COALESCE(visit_bookings.amount, 0) + COALESCE(visit_bookings.other_charges, 0)) as ticketAmount'),
				'visit_bookings.tax_percentage as ticketPercentage',
				'visit_bookings.cgst as ticketCgst',
				'visit_bookings.sgst as ticketSgst',
				'visit_bookings.igst as ticketIgst',
				'visit_bookings.agent_service_charges as agentServiceCharges',
				'visit_bookings.agent_tax_percentage as agentTaxPercentage',
				'visit_bookings.agent_cgst as agentCgst',
				'visit_bookings.agent_sgst as agentSgst',
				'visit_bookings.agent_igst as agentIgst',
				DB::raw('COALESCE(outlets.axapta_location_id, "") as axaptaLocationId'),
			])
				->join('agents', 'agents.id', 'visits.agent_id')
				->leftjoin('outlets', 'outlets.id', 'agents.outlet_id')
				->join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
				->where('visits.booking_method_id', 3042) //AGENT
				->where('visits.agent_ax_export_synched', 0) //NOT SYNCHED
				->groupBy('visits.id')
				->get();

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
				$cronLog->remarks = "Agent visits found";
            $datas = DB::table('axapta_exports')->where('entity_type_id',3790)->get()->toArray();
				$excel_header =[
					'EntityId',
					'CurrencyCode',
					'JournalName',
					'JournalNum',
					'LineNum',
					'Voucher',
					'ApproverPersonnelNumber',
					'Approved',
					'TransDate',
					'AccountType',
					'LedgerDimension',
					'DefaultDimension',
					'Txt',
					'AmountCurDebit',
					'AmountCurCredit',
					'OffsetAccountType',
					'OffsetLedgerDimension',
					'OffsetDefaultDimension',
					'PaymMode',
					'TaxGroup',
					'TaxItemGroup',
					'Invoice',
					'SalesTaxFormTypes_IN_FormType',
					'TDSGroup_IN',
					'DocumentNum',
					'DocumentDate',
					'LogisticsLocation_LocationId',
					'Due',
					'PaymReference',
				];
				foreach($datas as $data_key => $data){
				$excel_details =[
					$data->entity_id,
					$data->CurrencyCode,
					$data->JournalName,
					$data->JournalNum,
					$data->LineNum,
					$data->Voucher,
					$data->ApproverPersonnelNumber,
					$data->Approved,
					$data->TransDate,
					$data->AccountType,
					$data->LedgerDimension,
					$data->DefaultDimension,
					$data->Txt,
					$data->AmountCurDebit,
					$data->AmountCurCredit,
					$data->OffsetAccountType,
					$data->OffsetLedgerDimension,
					$data->OffsetDefaultDimension,
					$data->PaymMode,
					$data->TaxGroup,
					$data->TaxItemGroup,
					$data->Invoice,
					$data->SalesTaxFormTypes_IN_FormType,
					$data->TDSGroup_IN,
					$data->DocumentNum,
					$data->DocumentDate,
					$data->LogisticsLocation_LocationId,
					$data->Due,
					$data->PaymReference,
                ];
              	$export_data[] = $excel_details;
              	}
            	$time_stamp = date('Y_m_d_h_i_s');
		    	$outputfile = 'AgentAxaptaExport_' . $time_stamp;
				$file = Excel::create($outputfile, function ($excel) use ($excel_header,$export_data) {
					$excel->sheet('AgentAxaptaExport_', function ($sheet) use ($excel_header,$export_data) {
						$sheet->fromArray($export_data, NULL, 'A1');
						$sheet->row(1, $excel_header);
						$sheet->row(1, function ($row) {
						$row->setBackground('#07c63a');
						});
					});
				})->store('xlsx', storage_path('app/public/agent_axapta_report/'));
			} else {
				$cronLog->remarks = "No agent visits found";
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
		$employeeName = $trip->employee ? ($trip->employee->user ? $trip->employee->user->name : '') : '';
		$purpose = $trip->purpose ? $trip->purpose->name : '';
		$transactionDate = date('Y-m-d', strtotime($trip->created_at));
		$txt = '';

		//TOTAL AMOUNT ENTRY
		if ($type == 1) {

			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Vendor')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "V", $transactionDate, $accountType, $agentTripVisit->agentCode, "COMMON-MDS", $txt, 0.00, $agentTripVisit->totalAmount, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

		} elseif ($type == 2) {
			//TICKET ENTRIES

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

							$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_ECR", "D", $transactionDate, $accountType, $igstLedgerDimension, "COMMON-MDS", $igstEntryTxt, $agentTripVisit->ticketIgst, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);
						}
					}
				}
			}

		} elseif ($type == 3) {
			//AGENT SERVICE CHARGE ENTRIES

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
					$cgstPercentage = $sgstPercentage = ($agentTripVisit->agentTaxPercentage) / 2;
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

			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = "Tra - Exp " . $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Vendor')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			//BANK DEBIT ENTRY
			$this->saveAxaptaExport($trip->company_id, 3790, $agentTripVisit->visitId, "TLX_CHQ", "V", $transactionDate, $accountType, $agentTripVisit->agentCode, "COMMON-MDS", $txt, $agentTripVisit->totalAmount, 0.00, $agentTripVisit->invoiceNumber, $agentTripVisit->invoiceDate, $agentTripVisit->axaptaLocationId);

		} elseif ($type == 5) {
			//BANK CREDIT ENTRY

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

	public function cronGenerateEmployeeAxReport($cronLogId) {
		//CRON LOG SAVE
		$cronLog = CronLog::firstOrNew([
			'id' => $cronLogId,
		]);
		$cronLog->command = "generate:employee-ax-report";
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

			$employeeTrips = Trip::select([
				'trips.id',
				'trips.company_id',
				'trips.employee_id',
				'employees.code as employeeCode',
				'users.name as employeeName',
				'entities.name as purpose',
				DB::raw('DATE_FORMAT(trips.claimed_date,"%Y-%m-%D") as transactionDate'),
				'sbus.name as sbu',
				'outlets.code as outletCode',
				'outlets.axapta_location_id as axaptaLocationId',
				'ey_employee_claims.balance_amount as totalAmount',
				'ey_employee_claims.number as invoiceNumber',
				DB::raw('DATE_FORMAT(ey_employee_claims.created_at,"%Y-%m-%d") as invoiceDate'),
			])
				->join('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id')
				->join('employees', 'employees.id', 'trips.employee_id')
				->join('sbus', 'sbus.id', 'employees.sbu_id')
				->join('users', function ($join) {
					$join->on('users.entity_id', '=', 'employees.id')
						->where('users.user_type_id', 3121) //EMPLOYEE
					;
				})
				->join('outlets', 'outlets.id', 'employees.outlet_id')
				->join('entities', 'entities.id', 'trips.purpose_id')
				->where('ey_employee_claims.status_id', 3026) //PAID
				->where('trips.self_ax_export_synched', 0) //NOT SYNCHED
				->groupBy('trips.id')
				->get();

			$exceptionErrors = [];
			if ($employeeTrips->isNotEmpty()) {
				foreach ($employeeTrips as $key => $employeeTrip) {
					DB::beginTransaction();
					try {

						//TOTAL AMOUNT ENTRY
						$this->employeeAxaptaExportProcess(1, $employeeTrip, $axaptaAccountTypes, $axaptaBankDetails);

						//TAXABLE VALUE AND GST SPLITUP ENTRIES
						$this->employeeAxaptaExportProcess(2, $employeeTrip, $axaptaAccountTypes, $axaptaBankDetails);

						//BANK DEBIT ENTRY
						$this->employeeAxaptaExportProcess(3, $employeeTrip, $axaptaAccountTypes, $axaptaBankDetails);

						//BANK CREDIT ENTRY
						$this->employeeAxaptaExportProcess(4, $employeeTrip, $axaptaAccountTypes, $axaptaBankDetails);

						//AX SYNCHED
						Trip::where('id', $employeeTrip->id)->update([
							'self_ax_export_synched' => 1,
						]);

						DB::commit();
						continue;
					} catch (\Exception $e) {
						DB::rollBack();
						$exceptionErrors[] = "Trip ID ( " . $employeeTrip->id . " ) : " . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile();
						continue;
					}
				}
				$cronLog->remarks = "Employee trips found";
				$datas = DB::table('axapta_exports')->where('entity_type_id',3791)->get()->toArray();
					$excel_header =[
					'EntityId',
					'CurrencyCode',
					'JournalName',
					'JournalNum',
					'LineNum',
					'Voucher',
					'ApproverPersonnelNumber',
					'Approved',
					'TransDate',
					'AccountType',
					'LedgerDimension',
					'DefaultDimension',
					'Txt',
					'AmountCurDebit',
					'AmountCurCredit',
					'OffsetAccountType',
					'OffsetLedgerDimension',
					'OffsetDefaultDimension',
					'PaymMode',
					'TaxGroup',
					'TaxItemGroup',
					'Invoice',
					'SalesTaxFormTypes_IN_FormType',
					'TDSGroup_IN',
					'DocumentNum',
					'DocumentDate',
					'LogisticsLocation_LocationId',
					'Due',
					'PaymReference',
					];
				foreach($datas as $data_key => $data){
					$excel_details =[
					$data->entity_id,
					$data->CurrencyCode,
					$data->JournalName,
					$data->JournalNum,
					$data->LineNum,
					$data->Voucher,
					$data->ApproverPersonnelNumber,
					$data->Approved,
					$data->TransDate,
					$data->AccountType,
					$data->LedgerDimension,
					$data->DefaultDimension,
					$data->Txt,
					$data->AmountCurDebit,
					$data->AmountCurCredit,
					$data->OffsetAccountType,
					$data->OffsetLedgerDimension,
					$data->OffsetDefaultDimension,
					$data->PaymMode,
					$data->TaxGroup,
					$data->TaxItemGroup,
					$data->Invoice,
					$data->SalesTaxFormTypes_IN_FormType,
					$data->TDSGroup_IN,
					$data->DocumentNum,
					$data->DocumentDate,
					$data->LogisticsLocation_LocationId,
					$data->Due,
					$data->PaymReference,
                	];
              		$export_data[] = $excel_details;
              	}
            	$time_stamp = date('Y_m_d_h_i_s');
		    	$outputfile = 'EmployeeAxaptaExport_' . $time_stamp;
				$file = Excel::create($outputfile, function ($excel) use ($excel_header,$export_data) {
						$excel->sheet('EmployeeAxaptaExport_', function ($sheet) use ($excel_header,$export_data) {
						$sheet->fromArray($export_data, NULL, 'A1');
						$sheet->row(1, $excel_header);
						$sheet->row(1, function ($row) {
						$row->setBackground('#07c63a');
						});
					});
				})->store('xlsx', storage_path('app/public/employee_axapta_report/'));
			} else {
				$cronLog->remarks = "No employee trips found";
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

	public function employeeAxaptaExportProcess($type, $employeeTrip, $axaptaAccountTypes, $axaptaBankDetails) {
		$employeeCode = $employeeTrip->employeeCode;
		$employeeName = $employeeTrip->employeeName;
		$purpose = $employeeTrip->purpose;
		$defaultDimension = $employeeTrip->sbu . "-" . $employeeTrip->outletCode;
		$transactionDate = $employeeTrip->transactionDate;
		$txt = '';

		//TOTAL AMOUNT ENTRY
		if ($type == 1) {

			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Vendor')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_ECR", "V", $transactionDate, $accountType, $employeeCode, $defaultDimension, $txt, 0.00, $employeeTrip->totalAmount, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);

		} elseif ($type == 2) {
			//TAXABLE VALUE AND GST SPLITUP ENTRIES

			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Ledger')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';
			$ledgerDimension = "4572-" . $employeeTrip->sbu . "-" . $employeeTrip->outletCode;

			$employeeTransportAmount = 0.00;
			$employeeTransportOtherCharges = 0.00;
			$employeeTransportTaxableValue = 0.00;
			$employeeLodgingTaxableValue = 0.00;
			$employeeBoardingTaxableValue = 0.00;
			$employeeLocalTravelTaxableValue = 0.00;
			$employeeTotalTaxableValue = 0.00;

			//FARE DETAILS
			if ($employeeTrip->selfVisits->isNotEmpty()) {
				foreach ($employeeTrip->selfVisits as $key => $selfVisit) {
					if ($selfVisit->booking) {
						$employeeTransportAmount += $selfVisit->booking->amount;
						$employeeTransportOtherCharges += $selfVisit->booking->other_charges;
					}
				}
			}
			$employeeTransportTaxableValue = floatval($employeeTransportAmount + $employeeTransportOtherCharges);

			//LODGING
			if ($employeeTrip->lodgings->isNotEmpty()) {
				$employeeLodgingTaxableValue = floatval($employeeTrip->lodgings()->sum('amount'));
			}

			//BOARDING
			if ($employeeTrip->boardings->isNotEmpty()) {
				$employeeBoardingTaxableValue = floatval($employeeTrip->boardings()->sum('amount'));
			}

			//LOCAL TRAVELS
			if ($employeeTrip->localTravels->isNotEmpty()) {
				$employeeLocalTravelTaxableValue = floatval($employeeTrip->localTravels()->sum('amount'));
			}

			$employeeTotalTaxableValue = floatval($employeeTransportTaxableValue + $employeeLodgingTaxableValue + $employeeBoardingTaxableValue + $employeeLocalTravelTaxableValue);

			//TICKET TAXABLE AMOUNT ENTRY
			$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_ECR", "D", $transactionDate, $accountType, $ledgerDimension, $defaultDimension, $txt, $employeeTotalTaxableValue, 0.00, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);

			//GST SPLITUPS
			if ($employeeTrip->employee && $employeeTrip->employee->outlet && $employeeTrip->employee->outlet->address && $employeeTrip->employee->outlet->address->city && $employeeTrip->employee->outlet->address->city->state) {
				$employeeGstCode = !empty($employeeTrip->employee->outlet->address->city->state->gstin_state_code) ? $employeeTrip->employee->outlet->address->city->state->gstin_state_code : '';

				//FARE DETAILS GST SPLITUPS
				if ($employeeTrip->selfVisits->isNotEmpty()) {
					foreach ($employeeTrip->selfVisits as $key => $selfVisit) {
						if ($selfVisit->booking && !empty($selfVisit->booking->gstin)) {
							$this->axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $selfVisit->booking->gstin, "Tra - Exp ", $transactionDate, $accountType, 5, $selfVisit->booking->cgst, $selfVisit->booking->sgst, $selfVisit->booking->igst);
						}
					}
				}

				//LODGING GST SPLITUPS
				if ($employeeTrip->lodgings->isNotEmpty()) {
					foreach ($employeeTrip->lodgings as $key => $lodging) {
						//HAS MULTIPLE TAX INVOICE
						if ($lodging->has_multiple_tax_invoice == "Yes") {
							//LODGE
							if ($lodging->lodgingTaxInvoice) {
								$this->axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $lodging->gstin, "Lodging ", $transactionDate, $accountType, $lodging->tax_percentage, $lodging->lodgingTaxInvoice->cgst, $lodging->lodgingTaxInvoice->sgst, $lodging->lodgingTaxInvoice->igst);
							}

							//DRY WASH
							if ($lodging->drywashTaxInvoice) {
								$this->axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $lodging->gstin, "Lodging - Dry Wash ", $transactionDate, $accountType, $lodging->tax_percentage, $lodging->drywashTaxInvoice->cgst, $lodging->drywashTaxInvoice->sgst, $lodging->drywashTaxInvoice->igst);
							}

							//BOARDING
							if ($lodging->boardingTaxInvoice) {
								$this->axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $lodging->gstin, "Lodging - Boarding ", $transactionDate, $accountType, $lodging->tax_percentage, $lodging->boardingTaxInvoice->cgst, $lodging->boardingTaxInvoice->sgst, $lodging->boardingTaxInvoice->igst);
							}

							//OTHERS
							if ($lodging->othersTaxInvoice) {
								$this->axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $lodging->gstin, "Lodging - Others", $transactionDate, $accountType, $lodging->tax_percentage, $lodging->othersTaxInvoice->cgst, $lodging->othersTaxInvoice->sgst, $lodging->othersTaxInvoice->igst);
							}

						} else {
							//SINGLE
							$this->axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $lodging->gstin, "Lodging ", $transactionDate, $accountType, $lodging->tax_percentage, $lodging->cgst, $lodging->sgst, $lodging->igst);
						}
					}
				}

			}

		} elseif ($type == 3) {
			//BANK DEBIT ENTRY

			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Vendor')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			//BANK DEBIT ENTRY
			$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_CHQ", "V", $transactionDate, $accountType, $employeeCode, $defaultDimension, $txt, $employeeTrip->totalAmount, 0.00, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);

		} elseif ($type == 4) {
			//BANK CREDIT ENTRY

			if (!empty($employeeCode) && !empty($employeeName) && !empty($purpose)) {
				$txt = $employeeCode . " - " . $employeeName . " - " . $purpose;
			}
			$axaptaAccountType = $axaptaAccountTypes->where('name', 'Bank')->first();
			$accountType = $axaptaAccountType ? $axaptaAccountType->code : '';

			$axaptaBankDetail = $axaptaBankDetails->where('name', 'HDFC Bank')->first();
			$ledgerDimension = $axaptaBankDetail ? $axaptaBankDetail->code : '';

			//BANK CREDIT ENTRY
			$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_CHQ", "D", $transactionDate, $accountType, $ledgerDimension, $defaultDimension, $txt, 0.00, $employeeTrip->totalAmount, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);

		}
	}

	public function axaptaExportGstSplitupEntries($employeeTrip, $employeeGstCode, $enteredGstin, $gstType, $transactionDate, $accountType, $taxPercentage, $taxCgst, $taxSgst, $taxIgst) {
		$enteredGstinCode = substr($enteredGstin, 0, 2);
		$enteredGstinState = Nstate::where('gstin_state_code', $enteredGstinCode)->first();
		$gstDefaultDimension = $employeeTrip->sbu . "-" . $employeeTrip->outletCode;

		if ($enteredGstinState) {
			//INTRA STATE (CGST AND SGST)
			if ($enteredGstinCode == $employeeGstCode) {
				$cgstPercentage = $sgstPercentage = ($taxPercentage) / 2;
				$cgstEntryTxt = '';
				$sgstEntryTxt = '';
				$cgstLedgerDimension = $enteredGstinState->axapta_cgst_code . "-" . $employeeTrip->sbu . "-" . $employeeTrip->outletCode;
				$sgstLedgerDimension = $enteredGstinState->axapta_sgst_code . "-" . $employeeTrip->sbu . "-" . $employeeTrip->outletCode;

				if (!empty($employeeTrip->employeeCode) && !empty($employeeTrip->employeeName) && !empty($employeeTrip->purpose)) {
					$cgstEntryTxt = $gstType . " - " . $employeeTrip->employeeCode . " - " . $employeeTrip->employeeName . " - " . $employeeTrip->purpose . " - CGST - " . $cgstPercentage . "%";
				}
				if (!empty($employeeTrip->employeeCode) && !empty($employeeTrip->employeeName) && !empty($employeeTrip->purpose)) {
					$sgstEntryTxt = $gstType . " - " . $employeeTrip->employeeCode . " - " . $employeeTrip->employeeName . " - " . $employeeTrip->purpose . " - SGST - " . $sgstPercentage . "%";
				}

				//CGST ENTRY
				$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_ECR", "D", $transactionDate, $accountType, $cgstLedgerDimension, $gstDefaultDimension, $cgstEntryTxt, $taxCgst, 0.00, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);

				//SGST ENTRY
				$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_ECR", "D", $transactionDate, $accountType, $sgstLedgerDimension, $gstDefaultDimension, $sgstEntryTxt, $taxSgst, 0.00, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);

			} else {
				//INTER STATE (IGST)
				$igstPercentage = $taxPercentage;
				$igstEntryTxt = '';
				$igstLedgerDimension = $enteredGstinState->axapta_igst_code . "-" . $employeeTrip->sbu . "-" . $employeeTrip->outletCode;

				if (!empty($employeeTrip->employeeCode) && !empty($employeeTrip->employeeName) && !empty($employeeTrip->purpose)) {
					$igstEntryTxt = $gstType . " - " . $employeeTrip->employeeCode . " - " . $employeeTrip->employeeName . " - " . $employeeTrip->purpose . " - IGST - " . $igstPercentage . "%";
				}

				$this->saveAxaptaExport($employeeTrip->company_id, 3791, $employeeTrip->id, "TLX_ECR", "D", $transactionDate, $accountType, $igstLedgerDimension, $gstDefaultDimension, $igstEntryTxt, $taxIgst, 0.00, $employeeTrip->invoiceNumber, $employeeTrip->invoiceDate, $employeeTrip->axaptaLocationId);
			}
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
   //GST REPORT NEW ONE
	public function employeeGstrReport(Request $r) {
		ob_end_clean();
		$date = explode(' to ', $r->period);
		$from_date = date('Y-m-d', strtotime($date[0]));
		$to_date = date('Y-m-d', strtotime($date[1]));
		$business_ids = $r->businesses;
		if ($r->businesses) {
			if (in_array('-1', json_decode($r->businesses))) {
				$business_ids = Business::pluck('id')->toArray();
			} else {
				$business_ids = json_decode($r->businesses);
			}
		}
		ini_set('max_execution_time', 0);
		$excel_headers = [
			'Business GSTIN',
			'Business Unit',
			'Tax Period',
			'Doc Type',
			'Purchase Type',
			'Doc No',
			'Doc Date',
			'ERP Reference No',
			'Supplier GSTIN',
			'Supplier Name',
			'Supplier Address',
			'Supplier State',
			'Place of Supply',
			'Bill Of Entry Date',
			'Bill Of Entry No',
			'Port Code',
			'Reference Doc No',
			'Reference Doc Date',
			'Reverse Charge',
			'Discount',
			'Other Charges',
			'Round Off',
			'Doc Value',
			'Doc Tags',
			'Keywords',
			'Doc Label1',
			'Doc Label2',
			'Doc Label3',
			'ItemType',
			'Item Description',
			'HSN Code',
			'Hsn Description',
			'Unit Of Measure',
			'Qty',
			'Item Taxable Value',
			'Tax Category',
			'GST Rate',
			'IGST',
			'CGST',
			'SGST',
			'Cess Rate',
			'Cess',
		];
		$gst_transports = EmployeeClaim::select(
			DB::raw('COALESCE(operating_states.gst_number, "") as business_gstin'),
			DB::raw('COALESCE(sbus.name, "") as business_unit'),
			DB::raw('COALESCE(visit_bookings.invoice_number, "") as doc_number'),
			DB::raw('COALESCE(DATE_FORMAT(visit_bookings.invoice_date,"%d-%m-%Y"), "") as doc_date'),
			DB::raw('COALESCE(visit_bookings.gstin, "") as supplier_gstin'),
			DB::raw('COALESCE(visit_bookings.gstin_name, "") as supplier_name'),
			DB::raw('COALESCE(visit_bookings.gstin_address, "") as supplier_address'),
			DB::raw('COALESCE(visit_bookings.gstin_state_code, "") as supplier_state'),
			DB::raw('COALESCE(entities.name, "") as item_description'),
			DB::raw('COALESCE(entities.hsn_code, "") as hsn_code'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.amount, 0)),2,"en_IN") as item_taxable_amount'),
			DB::raw('COALESCE(visit_bookings.tax_percentage, "") as tax_percentage'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.cgst, 0)),2,"en_IN") as cgst'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.sgst, 0)),2,"en_IN") as sgst'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.igst, 0)),2,"en_IN") as igst'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.other_charges, 0)),2,"en_IN") as other_charges'),
			DB::raw('format(ROUND(IFNULL(visit_bookings.round_off, 0)),2,"en_IN") as round_off'),
			DB::raw('COALESCE(DATE_FORMAT(trips.claimed_date,"%d-%m-%Y"), "") as tax_period')
		)->leftJoin('employees', 'employees.id', 'ey_employee_claims.employee_id')
			->leftJoin('sbus', 'sbus.id', 'ey_employee_claims.sbu_id')
			->leftJoin('users', function ($user_q) {
				$user_q->on('employees.id', 'users.entity_id')
					->where('users.user_type_id', 3121);
			})
			->leftJoin('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->leftJoin('visits', 'visits.trip_id', 'trips.id')
			->leftJoin('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
			->leftJoin('entities','entities.id','visit_bookings.travel_mode_id')
			->leftJoin('outlets', 'outlets.id', 'trips.outlet_id')
			->leftJoin('ey_addresses as a', function ($join) {
				$join->on('a.entity_id', '=', 'outlets.id')
					->where('a.address_of_id', 3160);
			})
			->leftJoin('ncities as city', 'city.id', 'a.city_id')
			->leftJoin('nstates as s', 's.id', 'city.state_id')
			->leftjoin('operating_states','operating_states.nstate_id','s.id')
			->leftjoin('departments', 'departments.id', 'employees.department_id')
			->leftjoin('businesses', 'businesses.id', 'departments.business_id')
			->where('entities.entity_type_id',502)
			->whereDate('trips.claimed_date', '>=', $from_date)
			->whereDate('trips.claimed_date', '<=', $to_date)
			->whereIn('departments.business_id', $business_ids)
			->where('ey_employee_claims.status_id', 3026)
			->where('trips.status_id', 3026)
			->whereNotNull('visit_bookings.gstin')
			->groupBy('trips.id')
			->get()->toArray();
		$gst_lodgings = EmployeeClaim::select(
			DB::raw('COALESCE(operating_states.gst_number, "") as business_gstin'),
			DB::raw('COALESCE(sbus.name, "") as business_unit'),
			DB::raw('COALESCE(lodgings.id, "") as id'),
			DB::raw('COALESCE(lodgings.reference_number, "") as doc_number'),
			DB::raw('COALESCE(DATE_FORMAT(lodgings.invoice_date,"%d-%m-%Y"), "") as doc_date'),
			DB::raw('COALESCE(lodgings.gstin, "") as supplier_gstin'),
			DB::raw('COALESCE(lodgings.lodge_name, "") as supplier_name'),
			DB::raw('COALESCE(lodgings.gstin_address, "") as supplier_address'),
			DB::raw('COALESCE(lodgings.gstin_state_code, "") as supplier_state'),
			DB::raw('COALESCE(configs.name, "") as item_description'),
			DB::raw('COALESCE(configs.hsn_code, "") as hsn_code'),
			DB::raw('format(ROUND(IFNULL(lodgings.amount, 0)),2,"en_IN") as item_taxable_amount'),
			DB::raw('COALESCE(lodgings.tax_percentage, "") as tax_percentage'),
			DB::raw('format(ROUND(IFNULL(lodgings.cgst, 0)),2,"en_IN") as cgst'),
			DB::raw('format(ROUND(IFNULL(lodgings.sgst, 0)),2,"en_IN") as sgst'),
			DB::raw('format(ROUND(IFNULL(lodgings.igst, 0)),2,"en_IN") as igst'),
			DB::raw('format(ROUND(IFNULL(lodgings.round_off, 0)),2,"en_IN") as round_off'),
			DB::raw('COALESCE(DATE_FORMAT(trips.claimed_date,"%d-%m-%Y"), "") as tax_period'),
			DB::raw('COALESCE(lodgings.has_multiple_tax_invoice, "") as has_multiple_tax_invoice')
		)->leftJoin('employees', 'employees.id', 'ey_employee_claims.employee_id')
			->leftJoin('sbus', 'sbus.id', 'ey_employee_claims.sbu_id')
			->leftJoin('users', function ($user_q) {
				$user_q->on('employees.id', 'users.entity_id')
					->where('users.user_type_id', 3121);
			})
			->leftJoin('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->leftJoin('lodgings', 'lodgings.trip_id', 'trips.id')
			->leftJoin('lodging_tax_invoices','lodging_tax_invoices.lodging_id','lodgings.id')
			->leftJoin('configs','configs.id','lodging_tax_invoices.type_id')
			->leftJoin('outlets', 'outlets.id', 'trips.outlet_id')
			->leftJoin('ey_addresses as a', function ($join) {
				$join->on('a.entity_id', '=', 'outlets.id')
					->where('a.address_of_id', 3160);
			})
			->leftJoin('ncities as city', 'city.id', 'a.city_id')
			->leftJoin('nstates as s', 's.id', 'city.state_id')
			->leftjoin('operating_states','operating_states.nstate_id','s.id')
			->leftjoin('departments', 'departments.id', 'employees.department_id')
			->leftjoin('businesses', 'businesses.id', 'departments.business_id')
			->where('configs.config_type_id',546)
			->whereDate('trips.claimed_date', '>=', $from_date)
			->whereDate('trips.claimed_date', '<=', $to_date)
			->whereIn('departments.business_id', $business_ids)
			->where('ey_employee_claims.status_id', 3026)
			->where('trips.status_id', 3026)
			->whereNotNull('lodgings.gstin')
			->groupBy('trips.id')
			->get()->toArray();
		$gst_datas = array_merge($gst_transports,$gst_lodgings);
		if (count($gst_datas) == 0) {
			Session()->flash('error', 'No Data Found');
			//return redirect()->to('/#!/gst/report');
		}
		$export_details = [];
		$s_no = 1;
		$s_no++;
		foreach ($gst_transports as $gst_transport_key => $gst_transport) {
			$transport_data = [
				$gst_transport['business_gstin'],
				$gst_transport['business_unit'],
				$gst_transport['tax_period'],
				'Invoice',
				'STD',
				$gst_transport['doc_number'],
				$gst_transport['doc_date'],
				' ',
				$gst_transport['supplier_gstin'],
				$gst_transport['supplier_name'],
				$gst_transport['supplier_address'],
				$gst_transport['supplier_state'],
				$gst_transport['supplier_state'],
				' ',
				'',
				'',
				'',
				'',
				'',
				'',
				$gst_transport['other_charges'],
				$gst_transport['round_off'],
				'',
				'',
				'',
				'',
				'',
				'',
				'S',
				$gst_transport['item_description'],
				$gst_transport['hsn_code'],
				'',
				'OTH',
				'1',
				$gst_transport['item_taxable_amount'],
				'TAX',
				$gst_transport['tax_percentage'] . "%",
				$gst_transport['igst'],
				$gst_transport['cgst'],
				$gst_transport['sgst'],
				'0',
				'0',
			];
			$export_details[] = $transport_data;
		}
		foreach($gst_lodgings as $gst_lodging_key => $gst_lodging){
			$lodging_data = [
				$gst_lodging['business_gstin'],
				$gst_lodging['business_unit'],
				$gst_lodging['tax_period'],
				'Invoice',
				'STD',
				$gst_lodging['doc_number'],
				$gst_lodging['doc_date'],
				' ',
				$gst_lodging['supplier_gstin'],
				$gst_lodging['supplier_name'],
				$gst_lodging['supplier_address'],
				$gst_lodging['supplier_state'],
				$gst_lodging['supplier_state'],
				' ',
				'',
				'',
				'',
				'',
				'',
				'',
				'',
				$gst_lodging['round_off'],
				'',
				'',
				'',
				'',
				'',
				'',
				'S',
				$gst_lodging['item_description'],
				$gst_lodging['hsn_code'],
				'',
				'OTH',
				'1',
				$gst_lodging['item_taxable_amount'],
				'TAX',
				$gst_lodging['tax_percentage'] . "%",
				$gst_lodging['igst'],
				$gst_lodging['cgst'],
				$gst_lodging['sgst'],
				'0',
				'0',
			];
			$export_details[] = $lodging_data;
			if($gst_lodging['has_multiple_tax_invoice'] == 1){
			$multiple_taxs=LodgingTaxInvoice::select(
			DB::raw('COALESCE(configs.name, "") as multiple_description'),
			DB::raw('format(ROUND(IFNULL(lodging_tax_invoices.without_tax_amount, 0)),2,"en_IN") as multiple_amount'),
			DB::raw('COALESCE(lodging_tax_invoices.tax_percentage, "") as multilple_tax_percentage'),
			DB::raw('format(ROUND(IFNULL(lodging_tax_invoices.cgst, 0)),2,"en_IN") as multilple_cgst'),
			DB::raw('format(ROUND(IFNULL(lodging_tax_invoices.sgst, 0)),2,"en_IN") as multilple_sgst'),
			DB::raw('format(ROUND(IFNULL(lodging_tax_invoices.igst, 0)),2,"en_IN") as multilple_igst'))
			->leftJoin('lodgings','lodgings.id','lodging_tax_invoices.lodging_id')
			->leftJoin('configs','configs.id','lodging_tax_invoices.type_id')
			->where('lodging_tax_invoices.lodging_id',$gst_lodging['id'])
			->get()->toArray();
				foreach($multiple_taxs as $multiple_tax_key => $multiple_tax){
					$multiple_lodging_data = [
					    $gst_lodging['business_gstin'],
						$gst_lodging['business_unit'],
						$gst_lodging['tax_period'],
						'Invoice',
						'STD',
						$gst_lodging['doc_number'],
						$gst_lodging['doc_date'],
						' ',
						$gst_lodging['supplier_gstin'],
						$gst_lodging['supplier_name'],
						$gst_lodging['supplier_address'],
						$gst_lodging['supplier_state'],
						$gst_lodging['supplier_state'],
						' ',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						$gst_lodging['round_off'],
						'',
						'',
						'',
						'',
						'',
						'',
						'S',
						$multiple_tax['multiple_description'],
						$gst_lodging['hsn_code'],
						'',
						'OTH',
						'1',
						$multiple_tax['multiple_amount'],
						'TAX',
						$multiple_tax['multilple_tax_percentage'] ."%",
						$multiple_tax['multilple_igst'],
						$multiple_tax['multilple_cgst'],
						$multiple_tax['multilple_sgst'],
						'0',
						'0',
					];
			   		$export_details[] = $multiple_lodging_data;
		    	}
	        }
		}
		$title = 'GSTR2_REPORT_' . Carbon::now();
		$sheet_name = 'GSTR2 REPORT';
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
