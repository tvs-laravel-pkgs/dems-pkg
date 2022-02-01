<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Excel;
use Redirect;
use DB;
use Uitoux\EYatra\Trip;
use App\ReportDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class ExportReportController extends Controller
{
    // Bank statement report
    public function bankStatement(Request $r) {
            $time_stamp = date('Y_m_d_h_i_s');
            $outstations = Employee::select(
                'employees.code as Account_Number',
                'u.name as Name',
                'bd.account_number as Bank_Account_Number',
                't.description as Purpose',
                't.created_at as Created_Date_and_Time',
                't.company_id as company_id',
                't.claimed_date as documentdate',
                't.id as invoice',
                'eyec.total_amount as Amount',
                'eyec.number as documentnum',
                'ol.code as ledgerdiamension',
            )
                ->join('users as u','u.entity_id', 'employees.id')
                ->join('bank_details as bd', 'bd.entity_id', 'employees.id')
                ->join('trips as t','t.employee_id','employees.id')
                ->join('ey_employee_claims as eyec','eyec.employee_id','employees.id')
                ->join('outlets as ol','ol.id','employees.outlet_id')
                ->where('t.status_id','=','3026')
                ->get()->toArray();
                //dd($outstations);
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
                'ol.code as ledgerdiamension',
            )
                ->join('users as u','u.entity_id', 'employees.id')
                ->join('bank_details as bd', 'bd.entity_id', 'employees.id')
                ->join('local_trips as lt','lt.employee_id','employees.id')
                ->join('outlets as ol','ol.id','employees.outlet_id')
                ->where('lt.status_id','=','3026')
                ->get()->toArray();
                //dd($claims);
                $locals=array_merge($claims,$outstations);
           if(count($locals) > 0){
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
                $local_trips=array();
                $travelex_details=array();
                $voucher='v';
                $dataareaid='tvs';
                $journaltype='0';
                $journalname='TLXCHQ';
                $soprostatus='2';
                $company='tvs';
                $offsetledgerdiamension='0';
                $personnelnumber='INTG01';
                $logisticlocation='T V Sundram Iyengar & Sons P Ltd(CEN)';
                $transdate=$time_stamp;
                $account_type='2';
                $account_type_two='0';
                $offsetaccounttype='0';
                $offsetaccounttype_two='2';
                $amountcurcredit='00.00';
                //$amountcurcredit_two=$local['Amount'];
                //$amountcurdebit=$local['Amount'];
                $amountcurdebit_two='0.00';
                $posted='0';
                $batch='1';
                $total_amount=0;
                $s_no=1;
                $l_no=1;
                foreach ($locals as $key => $local) {
                    $total_amount +=$local['Amount'];
                $local_trip=[
                      $l_no++,
                     'EMP_'.$local['Account_Number'],
                     $local['Name'],
                     $local['Bank_Account_Number'],
                     '('.$local['Account_Number']. '-' .$local['Name'].')'. '-' .$local['Purpose'],
                     $local['Created_Date_and_Time'],
                     $local['Amount'],
                     $posted,
                     $batch,
                ];
                $travelex_local=[
                    $s_no++,
                   'AC-'.$local['invoice'],
                   $local['documentnum'],
                   $local['documentdate'],
                   'AL-'.$local['ledgerdiamension'],
                   $transdate,
                   $account_type,
                   $amountcurcredit,
                   $offsetaccounttype,
                   $local['Amount'],
                   '('.$local['Account_Number']. '-' .$local['Name'].')'. '-' .$local['Purpose'],
                   $voucher='v',
                   $posted='0',
                   $dataareaid='tvs',
                   $journaltype='0',
                   $journalname='TLXCHQ',
                   $soprostatus='2',
                   'EMP_'.$local['Account_Number'],
                   $company='tvs',
                   'ALSERV-'.$local['ledgerdiamension'],
                   $offsetledgerdiamension='0',
                   $personnelnumber='INTG01',
                   $logisticlocation='T V Sundram Iyengar & Sons P Ltd(CEN)',
                ];
                $travelex_detail=[
                    $s_no++,
                   'AC-'.$local['invoice'],
                   $local['documentnum'],
                   $local['documentdate'],
                   'AL-'.$local['ledgerdiamension'],
                   $transdate,
                   $account_type_two,
                   $local['Amount'],
                   $offsetaccounttype_two,
                   $amountcurdebit_two,
                   '('.$local['Account_Number']. '-' .$local['Name'].')'. '-' .$local['Purpose'],
                   $voucher='v',
                   $posted='0',
                   $dataareaid='tvs',
                   $journaltype='0',
                   $journalname='TLXCHQ',
                   $soprostatus='2',
                   '1215-'.$local['ledgerdiamension'].'-ALSERV',
                   $company='tvs',
                   'ALSERV-'.$local['ledgerdiamension'],
                   $offsetledgerdiamension='0',
                   $personnelnumber='INTG01',
                   $logisticlocation='T V Sundram Iyengar & Sons P Ltd(CEN)',
                ];
                $local_trips[] = $local_trip;
                $travelex_details[]=$travelex_local;
                $travelex_details[]=$travelex_detail;
            }
        }
                $consolidation_local=[
                    $s_no++,
                   'CC-'.$local['invoice'],
                   '0001',
                   $local['documentdate'],
                   'AL-'.$local['ledgerdiamension'],
                   $transdate,
                   '6',
                   $total_amount,
                   '2',
                   '0.00',
                   'consolidation',
                   $voucher='v',
                   $posted='0',
                   $dataareaid='tvs',
                   $journaltype='0',
                   $journalname='TLXCHQ',
                   $soprostatus='2',
                   'TVS-044',
                   $company='tvs',
                   'F&A-TVM',
                   $offsetledgerdiamension='0',
                   $personnelnumber='INTG01',
                   $logisticlocation='T V Sundram Iyengar & Sons P Ltd(CEN)',
                ];
                $consolidation_detail=[
                    $s_no++,
                   'CC-'.$local['invoice'],
                   '0001',
                   $local['documentdate'],
                   'AL-'.$local['ledgerdiamension'],
                   $transdate,
                   '0',
                   '0.00',
                   '0',
                   $total_amount,
                   'consolidation',
                   $voucher='v',
                   $posted='0',
                   $dataareaid='tvs',
                   $journaltype='0',
                   $journalname='TLXCHQ',
                   $soprostatus='2',
                   '1215-TVM-F&A',
                   $company='tvs',
                   'F&A-TVM',
                   $offsetledgerdiamension='0',
                   $personnelnumber='INTG01',
                   $logisticlocation='T V Sundram Iyengar & Sons P Ltd(CEN)',
                ];
                $travelex_details[]=$consolidation_local;
                $travelex_details[]=$consolidation_detail;
            ob_end_clean();
            ob_start();
             $outputfile ='_travelex_report' . $time_stamp;
            $file=Excel::create('travelex_' . $time_stamp, function ($excel) use ($travelex_header,$travelex_details) {
                $excel->sheet('travelex_', function ($sheet) use ($travelex_header,$travelex_details) {
                    $sheet->fromArray($travelex_details, NULL, 'A1');
                    $sheet->row(1, $travelex_header);
                    $sheet->row(1, function ($row) {
                    $row->setBackground('#07c63a');
                    });
                });
            })->store('xlsx',storage_path('app/public/travelex_report/'));
            //dd($file);
            //SAVE TRAVELEX REPORTS
                $report_details = new ReportDetail;
                $report_details->company_id = $local['company_id'];
                $report_details->type_id=3722;
                $report_details->name=$file->filename;
                $report_details->path='storage/app/public/travelex_report/' . $outputfile . '.xlsx';
                $report_details->batch=$batch;
                $report_details->no_of_credits=$s_no;
                $report_details->bank_date=$time_stamp;
                $report_details->credit_total_amount=$total_amount;
                $report_details->save();
                $outputfile_bank ='_travelex_report' . $time_stamp;
       $file_one=Excel::create('bank_statement_' . $time_stamp, function ($excel) use ($local_trips_header,$local_trips) {
                $excel->sheet('Bank Statement', function ($sheet) use ($local_trips_header,$local_trips) {
                    $sheet->fromArray($local_trips, NULL, 'A1');
                    $sheet->row(1, $local_trips_header);
                    $sheet->row(1, function ($row) {
                        $row->setBackground('#07c63a');
                    });
                });
            })->save('xlsx',storage_path('app/public/bank_statement_report/'));
       //dd($file_one);
    
        //SAVE BANK STATEMENT REPORTS
                $report_details = new ReportDetail;
                $report_details->company_id = $local['company_id'];
                $report_details->type_id=3721;
                $report_details->name=$file_one->filename;
                $report_details->path='storage/app/public/bank_statement_report/' . $outputfile_bank . '.xlsx';
                $report_details->batch=$batch;
                $report_details->no_of_credits=$l_no;
                $report_details->bank_date=$time_stamp;
                $report_details->credit_total_amount=$total_amount;
                $report_details->save();
        return Redirect::to('/');
    }
        
    // Travel X to Ax report
    public function travelXtoAx(Request $r) {
        
    }
    // Gst report
    public function gst(Request $r) {
        
    }
}
