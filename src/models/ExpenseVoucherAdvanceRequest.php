<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Uitoux\EYatra\ActivityLog;
use Uitoux\EYatra\ApprovalLog;
use Validator;
use Auth;
use App\Portal;
use Uitoux\EYatra\Config;
use Config as dataBaseConfig;

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
			'expense_voucher_advance_request_claims.remarks as claim_rejecion_remarks',
			'advance_pcv_claim_statuses.name as advance_pcv_claim_status',
			'employees.code',
			'employees.id as employee_id',
			'employees.payment_mode_id',
			'users.name',
			'employee_return_payment_modes.name as employee_return_payment_mode',
			'expense_voucher_advance_requests.rejection_id',
			'expense_voucher_advance_requests.remarks as advance_request_rejecion_remarks',
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

		$advancePcv = $this;
		if(!empty($advancePcv->employee->department) && $advancePcv->employee->department->business_id == 2){
			$transactionDetail = $advancePcv->company ? $advancePcv->company->oeslAdvancePcvPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($advancePcv->company->oes_business_unit->name) ? $advancePcv->company->oes_business_unit->name : null;
			$companyCode = isset($advancePcv->company->oes_business_unit->code) ? $advancePcv->company->oes_business_unit->code : null;
		}else if(!empty($advancePcv->employee->department) && $advancePcv->employee->department->business_id == 3){
			$transactionDetail = $advancePcv->company ? $advancePcv->company->hondaAdvancePcvPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($advancePcv->company->oem_business_unit->name) ? $advancePcv->company->oem_business_unit->name : null;
			$companyCode = isset($advancePcv->company->oem_business_unit->code) ? $advancePcv->company->oem_business_unit->code : null;
		}else if(!empty($advancePcv->employee->department) && $advancePcv->employee->department->business_id == 8){
			$transactionDetail = $advancePcv->company ? $advancePcv->company->investmentAdvancePcvPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($advancePcv->company->investment_business_unit->name) ? $advancePcv->company->investment_business_unit->name : null;
			$companyCode = isset($advancePcv->company->investment_business_unit->code) ? $advancePcv->company->investment_business_unit->code : null;
		}else{
			$transactionDetail = $advancePcv->company ? $advancePcv->company->dlobAdvancePcvPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($advancePcv->company->oem_business_unit->name) ? $advancePcv->company->oem_business_unit->name : null;
			$companyCode = isset($advancePcv->company->oem_business_unit->code) ? $advancePcv->company->oem_business_unit->code : null;
		}
		
		$invoiceSource = 'Travelex';
		$documentType = 'PCV Reimbursement';
		if (!empty($transactionDetail)) {
			$invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
			$documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
		}

		$businessUnitName = $companyBusinessUnit;
		$invoiceNumber = $advancePcv->number;
		$advancePcvApprovalLog = ApprovalLog::select([
			'id',
			DB::raw('DATE_FORMAT(approved_at,"%Y-%m-%d") as approved_date'),
		])
			->where('type_id', 3585) //Advance Expenses
			->where('approval_type_id', 3614) //Advance Expenses Request - Manager Approved
			->where('entity_id', $advancePcv->id)
			->first();
		$advancePcvApprovedDate = null;
		if($advancePcvApprovalLog){
			$advancePcvApprovedDate = $advancePcvApprovalLog->approved_date;
		}

		$invoiceDate = $advancePcvApprovedDate;
		$employeeData = $advancePcv->employee;
		$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
		$invoiceType = 'Prepayment';

		$description = '';
		if (!empty($employeeData->code)) {
			$description .= $employeeData->code;
		}
		if (!empty($employeeData->user->name)) {
			$description .= ',' . ($employeeData->user->name);
		}
		if (!empty($advancePcv->description)) {
			$description .= ',' . ($advancePcv->description);
		}

		$amount = $advancePcv->advance_amount;
		$outletCode = $advancePcv->outlet ? $advancePcv->outlet->oracle_code_l2 : null;
		$accountingClass = 'Purchase/Expense';
		$company = $advancePcv->company ? $advancePcv->company->oracle_code : null;
		$sbu = $employeeData->Sbu;
		$lob = $department = null;
		if (!empty($sbu)) {
			$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
			$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
		}
		$location = $outletCode;
		$naturalAccount = Config::where('id', 4131)->first()->name;
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
		$db_password = dataBaseConfig::set('database.connections.dynamic.password', $bpas_portal->db_password);
		DB::purge('dynamic');
		DB::reconnect('dynamic');

		$apInvoiceExport = DB::table('oracle_ap_invoice_exports')->where([
			'invoice_number' => $invoiceNumber,
			'business_unit' => $companyBusinessUnit,
			'invoice_source' => $invoiceSource,
			'document_type' => $documentType,
		])->first();
		if (!empty($apInvoiceExport)) {
			$res['errors'] = ['Invoice already exported to oracle table'];
			DB::setDefaultConnection('mysql');
			return $res;
		}

		saveApOracleExport($advancePcv->company_id, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $outletCode, $documentType, round($amount), $description, $accountingClass, $companyCode, $lob, $location, $department, $naturalAccount, );

		$res['success'] = true;
		DB::setDefaultConnection('mysql');
		return $res;
	}
}
