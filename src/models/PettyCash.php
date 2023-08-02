<?php
namespace Uitoux\EYatra;

use Uitoux\EYatra\PettyCashEmployeeDetails;
use App\Portal;
use Config as dataBaseConfig;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCash extends Model {
	use SoftDeletes;
	protected $table = 'petty_cash';

	protected $fillable = [
		'employee_id',
		'total',
		'status',
		'remarks',
		'date',
		'created_by',
	];

	public function company() {
		return $this->belongsTo('App\Company')->withTrashed();
	}

	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}

	public function generateClaimApOracleAxapta() {
		$res = [];
		$res['success'] = false;
		$res['errors'] = [];

		$pcv = $this;
		$companyId = $pcv->company_id;
		$companyBusinessUnit = isset($pcv->company->oem_business_unit->name) ? $pcv->company->oem_business_unit->name : null;
		$transactionDetail = $pcv->company ? $pcv->company->pcvInvoiceTransaction() : null;

		$invoiceSource = 'Travelex';
		$documentType = 'Invoice';
		if (!empty($transactionDetail)) {
			$invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
			$documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
		}

		$pcvDetail = PettyCashEmployeeDetails::select([
			'id',
			'invoice',
			'invoice_date',
			'invoice_number',
			'remarks',
		])
			->where('petty_cash_id', $pcv->id)
			->first();

		$amount = $pcv->total;
		$invoiceDate = $pcv->date;
		$invoiceNumber = $pcv->number;
		$dmsGrnNumber = null;
		$pcvInvoice = false;
		$description = null;
		if($pcvDetail){
			if($pcvDetail->invoice == 1){
				//INVOICE
				$invoiceDate = $pcvDetail->invoice_date;
				$invoiceNumber = $pcvDetail->invoice_number;
				$dmsGrnNumber = $pcv->number;
				$pcvInvoice = true;
			}
			$description = $pcvDetail->remarks;
		}

		$businessUnitName = $companyBusinessUnit;
		$employeeData = $pcv->employee;
		$customerCode = $employeeData ? $employeeData->code : null;
		$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
		$invoiceType = 'Standard';
		$outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
		$customerSiteNumber = $outletCode;
		$accountingClass = 'Purchase/Expense';
		$company  = isset($pcv->company->oem_business_unit->code) ? $pcv->company->oem_business_unit->code : null;
		$sbu = $employeeData->Sbu;
		$lob = $department = null;
		if ($sbu) {
			$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
			$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
		}
		$location = $outletCode;
		$naturalAccount = Config::where('id', 3923)->first()->name;
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

		if($pcvInvoice == true){
			$apInvoiceExports = DB::table('oracle_ap_invoice_exports')->where([
				'dms_grn_number' => $dmsGrnNumber,
				'business_unit' => $businessUnitName,
				'invoice_source' => $invoiceSource,
				'document_type' => $documentType,
			])->get();
		}else{
			$apInvoiceExports = DB::table('oracle_ap_invoice_exports')->where([
				'invoice_number' => $invoiceNumber,
				'business_unit' => $businessUnitName,
				'invoice_source' => $invoiceSource,
				'document_type' => $documentType,
			])->get();
		}
		if (count($apInvoiceExports) > 0) {
			$res['errors'] = ['Already exported to oracle table'];
			DB::setDefaultConnection('mysql');
			return $res;
		}

		saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $amount, $accountingClass, $company, $lob, $location, $department, $naturalAccount , $documentType);

		$res['success'] = true;
		DB::setDefaultConnection('mysql');
		return $res;
	}

}