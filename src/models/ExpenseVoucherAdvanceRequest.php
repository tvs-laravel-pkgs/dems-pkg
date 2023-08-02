<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Uitoux\EYatra\ActivityLog;
use Validator;
use Auth;
use App\Portal;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\ExpenseVoucherAdvanceRequestClaim;
use Config as dataBaseConfig;
use Carbon\Carbon;

class ExpenseVoucherAdvanceRequest extends Model {
	use SoftDeletes;
	protected $table = 'expense_voucher_advance_requests';
	protected $fillable = [
		'employee_id',
		'date',
		'advance_amount',
		// 'expense_amount',
		'status_id',
	];
	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}
	
	public function setDateAttribute($value) {
		return $this->attributes['date'] = $value ? date('Y-m-d', strtotime($value)) : date('Y-m-d');
	}

	public function getDateAttribute($value) {
		return date('d-m-Y', strtotime($value));
	}

	public function company() {
		return $this->belongsTo('App\Company')->withTrashed();
	}

	public static function getExpenseVoucherAdvanceRequestData($id){
		 $expense_voucher_advance_request = ExpenseVoucherAdvanceRequest::select([
			'expense_voucher_advance_requests.id',
			'expense_voucher_advance_requests.number as advance_pcv_number',
			'expense_voucher_advance_requests.date',
			'expense_voucher_advance_requests.advance_amount',
			'expense_voucher_advance_requests.description',
			'expense_voucher_advance_requests.status_id',
			'configs.name as status',
			'expense_voucher_advance_request_claims.number as advance_pcv_claim_number',
			'expense_voucher_advance_request_claims.expense_amount',
			'expense_voucher_advance_request_claims.balance_amount',
			'expense_voucher_advance_request_claims.description as expense_description',
			'expense_voucher_advance_request_claims.status_id as advance_pcv_claim_status_id',
			'advance_pcv_claim_statuses.name as advance_pcv_claim_status',
			'employees.code',
			'employees.id as employee_id',
			'employees.payment_mode_id',
			'users.name',
			'employee_return_payment_modes.name as employee_return_payment_mode',
		])
			->leftjoin('employees', 'employees.id', 'expense_voucher_advance_requests.employee_id')
			->leftjoin('users', 'users.entity_id', 'employees.id')
			->leftjoin('configs', 'configs.id', 'expense_voucher_advance_requests.status_id')
			->leftjoin('expense_voucher_advance_request_claims', 'expense_voucher_advance_request_claims.expense_voucher_advance_request_id', 'expense_voucher_advance_requests.id')
			->leftjoin('configs as advance_pcv_claim_statuses', 'advance_pcv_claim_statuses.id', 'expense_voucher_advance_request_claims.status_id')
			->leftjoin('configs as employee_return_payment_modes', 'employee_return_payment_modes.id', 'expense_voucher_advance_request_claims.employee_return_payment_mode_id')
			->where('users.user_type_id', 3121) //EMPLOYEE
			->where('expense_voucher_advance_requests.id', $id)
			->first();
		return $expense_voucher_advance_request;
	}

	public static function proofViewUpdate(Request $request){
		try {
			$error_messages = [
				'attachment_id.required' => 'Attachment ID is required',
				'attachment_id.integer' => 'Attachment ID is invalid',
				'attachment_id.exists' => 'Attachment data is not found',
				'expense_voucher_advance_request_id.required' => 'Expense Voucher Advance Request ID is required',
				'expense_voucher_advance_request_id.integer' => 'Expense Voucher Advance Request ID is invalid',
				'expense_voucher_advance_request_id.exists' => 'Expense Voucher Advance Request data is not found',
				'activity_id.required' => 'Activity ID is required',
				'activity_id.integer' => 'Activity ID is invalid',
				'activity_id.exists' => 'Activity data is not found',
				'activity.required' => 'Activity is required',
			];
			$validations = [
				'attachment_id' => 'required|integer|exists:attachments,id',
				'expense_voucher_advance_request_id' => 'required|integer|exists:expense_voucher_advance_requests,id',
				'activity_id' => 'required|integer|exists:configs,id',
				'activity' => 'required',
			];
			$validator = Validator::make($request->all(), $validations, $error_messages);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$activity['entity_id'] = $request->attachment_id;
			$activity['entity_type'] = 'Advance PCV Attachment';
			$activity['details'] = "Attachment is viewed";
			$activity['activity'] = $request->activity;
			$activity_log = ActivityLog::saveLog($activity);

			$advance_pcv_attachment_ids = Attachment::where('attachment_of_id', 3442)
				->where('attachment_type_id', 3200)
				->where('entity_id', $request->expense_voucher_advance_request_id)
				->pluck('id');
			$advance_pcv_attachment_count = Attachment::where('attachment_of_id', 3442)
				->where('attachment_type_id', 3200)
				->where('entity_id', $request->expense_voucher_advance_request_id)
				->count();
			$viewed_attachment_count = ActivityLog::where('user_id' , Auth::id())
				->whereIn('entity_id', $advance_pcv_attachment_ids)
				->where('entity_type_id', 4038)
				->where('activity_id', $request->activity_id)
				->count();

			$proof_view_pending = false;	
			if($advance_pcv_attachment_count != $viewed_attachment_count){
				$proof_view_pending = true;
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'proof_view_pending' => $proof_view_pending,
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => ['Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile()],
			]);
		}
	}


	public function generatePrePaymentApOracleAxapta() {
		$res = [];
		$res['success'] = false;
		$res['errors'] = [];

		$companyId = $this->company_id;
		$companyBusinessUnit = isset($this->company->oem_business_unit->name) ? $this->company->oem_business_unit->name : null;
		$companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;
		$transactionDetail = $this->company ? $this->company->advancePcvPrePaymentTransaction() : null;
		$invoiceSource = 'Travelex';
		$documentType = 'Invoice';
		if (!empty($transactionDetail)) {
			$invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
			$documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
		}

		$businessUnitName = $companyBusinessUnit;
		$invoiceNumber = $this->number;
		$invoiceDate = $this->date ? date("Y-m-d", strtotime($this->date)) : null;
		$employeeData = $this->employee;
		$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
		$invoiceType = 'Prepayment';
		$description = $this->description;

		$amount = $this->advance_amount;
		$outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
		$accountingClass = 'Purchase/Expense';
		$company = $this->company ? $this->company->oracle_code : '';

		$sbu = $employeeData->Sbu;
		$lob = $department = null;
		if ($sbu) {
			$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
			$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
		}
		$location = $outletCode;
		$naturalAccount = Config::where('id', 3862)->first()->name;
		$supplierSiteName = $outletCode;

		$bpas_portal = Portal::select([
			'db_host_name',
			'db_port_number',
			'db_name',
			'db_user_name',
			'db_password',
		])
			->where('id', 1)
			->first();
		DB::setDefaultConnection('dynamic');
		$db_host_name = dataBaseConfig::set('database.connections.dynamic.host', $bpas_portal->db_host_name);
		$db_port_number = dataBaseConfig::set('database.connections.dynamic.port', $bpas_portal->db_port_number);
		$db_port_driver = dataBaseConfig::set('database.connections.dynamic.driver', "mysql");
		$db_name = dataBaseConfig::set('database.connections.dynamic.database', $bpas_portal->db_name);
		$db_username = dataBaseConfig::set('database.connections.dynamic.username', $bpas_portal->db_user_name);
		$db_username = dataBaseConfig::set('database.connections.dynamic.password', $bpas_portal->db_password);
		DB::purge('dynamic');
		DB::reconnect('dynamic');

		$apInvoiceExports = DB::table('oracle_ap_invoice_exports')->where([
			'invoice_number' => $invoiceNumber,
			'business_unit' => $companyBusinessUnit,
			'invoice_source' => $invoiceSource,
		])->get();
		if (count($apInvoiceExports) > 0) {
			$res['errors'] = ['Already exported to oracle table'];
			DB::setDefaultConnection('mysql');
			return $res;
		}

		DB::table('oracle_ap_invoice_exports')->insert([
			'company_id' => $companyId,
			'business_unit' => $businessUnitName,
			'invoice_source' => $invoiceSource,
			'invoice_number' => $invoiceNumber,
			'invoice_date' => $invoiceDate,
			'supplier_number' => $supplierNumber,
			'supplier_site_name' => $supplierSiteName,
			'invoice_type' => $invoiceType,
			'invoice_description' => $description,
			'amount' => round($amount),
			'outlet' => $outletCode,
			'accounting_class' => $accountingClass,
			'company' => $companyCode,
			'lob' => $lob,
			'location' => $location,
			'department' => $department,
			'natural_account' => $naturalAccount,
			'document_type' => $documentType,
			'created_at' => Carbon::now(),
		]);

		$res['success'] = true;
		DB::setDefaultConnection('mysql');
		return $res;
	}

	public function generateClaimApOracleAxapta() {
		$res = [];
		$res['success'] = false;
		$res['errors'] = [];

		$advancePcv = $this;
		$companyId = $advancePcv->company_id;
		$companyBusinessUnit = isset($advancePcv->company->oem_business_unit->name) ? $advancePcv->company->oem_business_unit->name : null;
		$transactionDetail = $advancePcv->company ? $advancePcv->company->advancePcvClaimTransaction() : null;

		$invoiceSource = 'Travelex';
		$documentType = 'Invoice';
		if (!empty($transactionDetail)) {
			$invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
			$documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
		}

		$advancePcvClaim = ExpenseVoucherAdvanceRequestClaim::select([
			'id',
			'number',
			'expense_amount',
			'balance_amount',
			'description',
			'created_at',
		])
			->where('expense_voucher_advance_request_id', $advancePcv->id)
			->first();

		$invoiceAmount = null;
		$invoiceDate = null;
		$invoiceNumber = null;
		$prePaymentNumber = null;
		$prePaymentAmount = null;
		$description = null;
		if ($advancePcvClaim) {
			$invoiceAmount = round($advancePcvClaim->expense_amount);
			$invoiceDate = $advancePcvClaim->created_at ? date("Y-m-d", strtotime($advancePcvClaim->created_at)) : null;
			$invoiceNumber = $advancePcvClaim->number;

			if ($advancePcv->advance_amount && $advancePcv->advance_amount > 0) {
				$prePaymentNumber = $advancePcv->number;
				$prePaymentAmount = $advancePcv->advance_amount;
			}
			$description = $advancePcvClaim->description;
		}

		$businessUnitName = $companyBusinessUnit;
		$employeeData = $advancePcv->employee;
		$customerCode = $employeeData ? $employeeData->code : null;
		$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
		$invoiceType = 'Standard';
		$outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
		$customerSiteNumber = $outletCode;
		$accountingClass = 'Purchase/Expense';
		$company  = isset($advancePcv->company->oem_business_unit->code) ? $advancePcv->company->oem_business_unit->code : null;
		$sbu = $employeeData->Sbu;
		$lob = $department = null;
		if ($sbu) {
			$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
			$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
		}
		$location = $outletCode;
		$naturalAccount = Config::where('id', 3863)->first()->name;
		$empToCompanyNaturalAccount = Config::where('id', 3922)->first()->name;
		$supplierSiteName = $outletCode;

		$bpas_portal = Portal::select([
			'db_host_name',
			'db_port_number',
			'db_name',
			'db_user_name',
			'db_password',
		])
			->where('id', 1)
			->first();
		DB::setDefaultConnection('dynamic');
		$db_host_name = dataBaseConfig::set('database.connections.dynamic.host', $bpas_portal->db_host_name);
		$db_port_number = dataBaseConfig::set('database.connections.dynamic.port', $bpas_portal->db_port_number);
		$db_port_driver = dataBaseConfig::set('database.connections.dynamic.driver', "mysql");
		$db_name = dataBaseConfig::set('database.connections.dynamic.database', $bpas_portal->db_name);
		$db_username = dataBaseConfig::set('database.connections.dynamic.username', $bpas_portal->db_user_name);
		$db_username = dataBaseConfig::set('database.connections.dynamic.password', $bpas_portal->db_password);
		DB::purge('dynamic');
		DB::reconnect('dynamic');

		$apInvoiceExports = DB::table('oracle_ap_invoice_exports')->where([
			'invoice_number' => $invoiceNumber,
			'business_unit' => $businessUnitName,
			'invoice_source' => $invoiceSource,
		])->get();
		if (count($apInvoiceExports) > 0) {
			$res['errors'] = ['Already exported to oracle table'];
			DB::setDefaultConnection('mysql');
			return $res;
		}

		saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, $prePaymentNumber, $prePaymentAmount, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $invoiceAmount, $accountingClass, $company, $lob, $location, $department, $naturalAccount , $documentType);

		//IF ADVANCE RECEIVED
		if ($advancePcv->advance_amount && $advancePcv->advance_amount > 0) {
			if ($advancePcvClaim->balance_amount && $advancePcvClaim->balance_amount != '0.00') {
				//EMPLOYEE TO COMPANY
				if ($advancePcvClaim->balance_amount > 0) {
					saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, abs($advancePcvClaim->balance_amount), $accountingClass, $company, $lob, $location, $department, $empToCompanyNaturalAccount, $documentType);
				}
			}
		}

		$res['success'] = true;
		DB::setDefaultConnection('mysql');
		return $res;
	}
}
