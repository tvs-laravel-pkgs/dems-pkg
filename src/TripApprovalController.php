<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\AlternateApprove;
use Uitoux\EYatra\Trip;
use Yajra\Datatables\Datatables;

class TripApprovalController extends Controller {

	public function getList(Request $request) {

		$fromDate = NULL;
		$toDate = NULL;
		if (!empty($request->from_date)) {
			$fromDate = date('Y-m-d', strtotime($request->from_date));
		}
		if (!empty($request->to_date)) {
			$toDate = date('Y-m-d', strtotime($request->to_date));
		}

		$trips = Trip::select([
			'trips.id',
			DB::raw('COALESCE(ey_employee_claims.number, "--") as claim_number'),
			'trips.number',
			'employee.code as ecode',
			'users.name as ename',
			'trips.start_date',
			'trips.end_date',
			'trips.status_id',
			'purpose.name as purpose',
			DB::raw('IF((trips.advance_received) IS NULL,"--",FORMAT(trips.advance_received,2,"en_IN")) as advance_received'),
			DB::raw('DATE_FORMAT(MAX(trips.created_at),"%d/%m/%Y %h:%i %p") as date'),
			DB::raw('IF((trips.reason) IS NULL,"--",trips.reason) as reason'),
			'status.name as status', 'status.name as status_name',
		])
			->leftjoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id')
			->join('visits', 'visits.trip_id', 'trips.id')
			->join('employees as employee', 'employee.id', 'trips.employee_id')
			->leftjoin('employees as trip_manager_employee', 'trip_manager_employee.id', 'trips.manager_id')
			->leftjoin('employees as senior_manager_employee', 'senior_manager_employee.id', 'trip_manager_employee.reporting_to_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where(function ($query) use ($request) {
				if ($request->get('employee_id')) {
					$query->where("employee.id", $request->get('employee_id'))->orWhere(DB::raw("-1"), $request->get('employee_id'));
				}
			})
			->where(function ($query) use ($request) {
				if ($request->get('purpose_id')) {
					$query->where("purpose.id", $request->get('purpose_id'))->orWhere(DB::raw("-1"), $request->get('purpose_id'));
				}
			})
			->where(function ($query) use ($request) {
				if ($request->get('status_id')) {
					$query->where("status.id", $request->get('status_id'))->orWhere(DB::raw("-1"), $request->get('status_id'));
				}
			})
			->where(function ($query) use ($fromDate) {
				if (!empty($fromDate)) {
					$query->where('trips.start_date', $fromDate);
				}
			})
			->where(function ($query) use ($toDate) {
				if (!empty($toDate)) {
					$query->where('trips.end_date', $toDate);
				}
			})
			->where('users.user_type_id', 3121)
			->orderBy('trips.created_at', 'desc')
			->groupBy('trips.id')
		;

		if (Auth::user()->entity_id) {
			$sub_employee_id = AlternateApprove::select('employee_id')
				->where('from', '<=', date('Y-m-d'))
				->where('to', '>=', date('Y-m-d'))
				->where('alternate_employee_id', Auth::user()->entity_id)
				->get()
				->toArray();
			$ids = array_column($sub_employee_id, 'employee_id');
			array_push($ids, Auth::user()->entity_id);

			//OUTSTATION TRIP CLIAM VERIFICATION ONE
			if(Entrust::can('eyatra-indv-trip-verifications') && Entrust::can('eyatra-indv-employee-claims-verification1') && !Entrust::can('eyatra-indv-employee-claims-verification2')) {
				if(count($sub_employee_id) > 0) {
					$trips->whereIn('employee.reporting_to_id', $ids)//alternate manager
                       ->whereIn('trips.status_id', [3021,3023]) //CLAIM REQUESTED
					   ->where('employee.company_id', Auth::user()->company_id)
					;
				} else {
					$trips->where('employee.reporting_to_id', Auth::user()->entity_id)
						->whereIn('trips.status_id', [3021,3023]) //CLAIM REQUESTED
						->where('employee.company_id', Auth::user()->company_id)
					;
				}
			}else if(Entrust::can('eyatra-indv-trip-verifications') && Entrust::can('eyatra-indv-employee-claims-verification1') && Entrust::can('eyatra-indv-employee-claims-verification2')) {
				//OUTSTATION TRIP CLIAM VERIFICATION TWO
				if (count($sub_employee_id) > 0) {
					$trips->where(function($q) use ($ids){ //Alternate seniour manager
    				$q->where(function($reportingQ) use ($ids) {
        			$reportingQ->where('employee.reporting_to_id', $ids)
       				 ->whereIn('trips.status_id', [3021,3023]);
    				})
    				->orWhere(function($seniorQ) use ($ids) {
        			$seniorQ->where('senior_manager_employee.id', $ids)
        			->whereIn('trips.status_id', [3029]);
    				});
				})
				->where('employee.company_id', Auth::user()->company_id); 
				} else {
					$trips->where(function($q) use ($ids){
    				$q->where(function($reportingQ) use ($ids) {
        			$reportingQ->where('employee.reporting_to_id', Auth::user()->entity_id)
        			->whereIn('trips.status_id', [3021,3023]);
    				})
    				->orWhere(function($seniorQ) use ($ids) {
        			$seniorQ->where('senior_manager_employee.id', Auth::user()->entity_id)
        			->whereIn('trips.status_id', [3029]);
    				});
					})
			       ->where('employee.company_id', Auth::user()->company_id); 
			    }
			} else {
				$trips = collect();
			}
		} else {
			$trips = collect();
		}
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$action = '';
				//OUTSTATION TRIP CLIAM VERIFICATION ONE
				if (Entrust::can('eyatra-indv-trip-verifications') && Entrust::can('eyatra-indv-employee-claims-verification1') && !Entrust::can('eyatra-indv-employee-claims-verification2')) {
					//OUTSTATION TRIP VERIFICATIONS
					if($trip->status_id =='3021'){
					$action = '<a href="#!/trip/verification/form/' . $trip->id . '">
									<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
								</a>';
					}else if($trip->status_id == '3023'){
						$action = '<a href="#!/trip/claim/verification1/view/' . $trip->id . '">
									<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
								</a>';
					}
				}else if(Entrust::can('eyatra-indv-trip-verifications') && Entrust::can('eyatra-indv-employee-claims-verification1') && Entrust::can('eyatra-indv-employee-claims-verification2')) {
					//OUTSTATION TRIP CLIAM VERIFICATION TWO
					if($trip->status_id== '3021'){
					$action = '<a href="#!/trip/verification/form/' . $trip->id . '">
									<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
								</a>';
					}else if($trip->status_id== '3023'){
						$action = '<a href="#!/trip/claim/verification1/view/' . $trip->id . '">
									<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
								</a>';
					}else if($trip->status_id== '3029')
					$action = '<a href="#!/trip/claim/verification2/view/' . $trip->id . '">
									<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
								</a>';
				}
				return $action;

			})
			->addColumn('title', function ($trip) {
					if($trip->claim_number == '--'){
					$title ='Trip';
					}else{
						$title ='Claim';
					}
				return $title;

			})
			->make(true);
	}
}
