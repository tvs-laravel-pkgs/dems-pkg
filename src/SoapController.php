<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Artisaninweb\SoapWrapper\SoapWrapper;

class SoapController extends Controller
{
    protected $soapWrapper;

    /**
     * SoapController constructor.
     *
     * @param SoapWrapper $soapWrapper
     */
    public function __construct(SoapWrapper $soapWrapper) {
        $this->soapWrapper = $soapWrapper;
    }

    /**
     * Use the SoapWrapper
     */

    public function GetCMSEmployeeDetails($empCode = '') 
    {

        try {
            $response = array();
            $params = ['Employeecode' => $empCode,];
            $this->soapWrapper->add('Employee', function ($service) {
                $service
                    ->wsdl('https://tvsapp.tvs.in/OnGo/WebService.asmx?wsdl')
                    ->trace(true);
            });
            $employeeData=array();
            $employee_details = $this->soapWrapper->call('Employee.GetCMSEmployeeDetails', [$params]);
            if(isset($employee_details->GetCMSEmployeeDetailsResult)&&$employee_details->GetCMSEmployeeDetailsResult){
            $employeeData =$employee_details->GetCMSEmployeeDetailsResult->any;
           }
           //dd($employeeData);
            //$//customer_details = $this->soapWrapper->call('customer.GetNewCustMasterDetails_Search', [$params]);

            if (!empty((array) $employeeData)) {
                $xml = simplexml_load_string($employeeData);
                if (isset($xml->Table)) {
                    foreach ($xml->Table as $val) {
                        $temp = (array) $val;
                        $response['code'] = $temp['EMPCODE'];
                        $response['name'] = $temp['EMPNAME'];
                        $response['business_id'] = (isset($temp['FUNCTION']) && $temp['FUNCTION'] != "Not available") ? $temp['FUNCTION'] : '';
                        $response['department_id'] = (isset($temp['DEPARTMENT']) && $temp['DEPARTMENT'] != "Not available") ? $temp['DEPARTMENT'] : '';
                        $response['lob_id'] = (isset($temp['LOB']) && $temp['LOB'] != "Not available") ? $temp['LOB'] : '';
                        $response['sbu_id'] = (isset($temp['SBU']) && $temp['SBU'] != "Not available") ? $temp['SBU'] : '';
                        $response['outlet_id'] = (isset($temp['OUTLET']) && $temp['OUTLET'] != "Not available") ? $temp['OUTLET'] : '';
                        $response['designation_id'] = (isset($temp['DESIGNATION']) && $temp['DESIGNATION'] != "Not available") ? $temp['DESIGNATION'] : '';
                        $response['reporting_to'] = (isset($temp['REPORTING_MANAGER_ECODE']) && $temp['REPORTING_MANAGER_ECODE'] != "Not available") ? $temp['REPORTING_MANAGER_ECODE'] : '';
                        $response['reporting_to_name'] = (isset($temp['REPORTING_MANAGER']) && $temp['REPORTING_MANAGER'] != "Not available") ? $temp['REPORTING_MANAGER'] : '';
                        $response['email'] = (isset($temp['EMAILID']) && $temp['EMAILID'] != "Not available") ? $temp['EMAILID'] : '';
                        $response['role'] = (isset($temp['ROLE']) && $temp['ROLE'] != "Not available") ? $temp['ROLE'] : '';
                        $response['employee_type'] = (isset($temp['EMPLOYEE_TYPE']) && $temp['EMPLOYEE_TYPE'] != "Not available") ? $temp['EMPLOYEE_TYPE'] : '';
                        $response['outlet_id'] = (isset($temp['SITE']) && $temp['SITE'] != "Not available") ? $temp['SITE'] : '';
                        $response['proximity_card_no'] = (isset($temp['PROXIMITY_CARD_NO']) && $temp['PROXIMITY_CARD_NO'] != "Not available") ? $temp['PROXIMITY_CARD_NO'] : '';
                        $response['company'] = (isset($temp['COMPANY']) && $temp['COMPANY'] != "Not available") ? $temp['COMPANY'] : '';
                    }
                   $response['success'] = true;
                    return $response;
                } else {
                    
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            
            return false;
        }
    }
}
