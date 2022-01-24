<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class TripClaimController extends Controller {
	public function listEYatraTripClaimList(Request $r) {
		//dd('test');
		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('ey_employee_claims as claims', 'claims.trip_id', 'trips.id')
			->join('configs as status', 'status.id', 'claims.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'trips.status_id',
				// DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(trips.created_at,"%d-%m-%Y") as created_date'),
				'purpose.name as purpose',
				DB::raw('IF((trips.advance_received) IS NULL,"--",FORMAT(trips.advance_received,"2","en_IN")) as advance_received'),
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
				if ($r->from_date) {
					$date = date('Y-m-d', strtotime($r->from_date));
					$query->where("trips.start_date", $date)->orWhere(DB::raw("-1"), $r->from_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->to_date) {
					$date = date('Y-m-d', strtotime($r->to_date));
					$query->where("trips.end_date", $date)->orWhere(DB::raw("-1"), $r->to_date);
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('trip_id')) {
					$query->where("trips.id", $r->get('trip_id'))->orWhere(DB::raw("-1"), $r->get('trip_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('status_id')) {
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
			->where('trips.employee_id', Auth::user()->entity_id)
			->groupBy('trips.id')
		// ->orderBy('trips.created_at', 'desc');
			->orderBy('trips.id', 'desc');

		// if (!Entrust::can('view-all-trips')) {
		// 	$trips->where('trips.employee_id', Auth::user()->entity_id);
		// }
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';

				if ($trip->status_id == 3023 || $trip->status_id == 3024 || $trip->status_id == 3033) {
					$action .= ' <a href="#!/trip/claim/edit/' . $trip->id . '"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a> ';
				}

				$action .= ' <a href="#!/trip/claim/view/' . $trip->id . '"><img src="' . $img2 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a> ';
				return $action;
			})
			->make(true);
	}

	public function eyatraTripClaimFormData($trip_id) {
		return Trip::getClaimFormData($trip_id);
	}
	public function eyatraTripClaimFilterData() {
		return Trip::getFilterData($type = 2);
	}

	public function saveEYatraTripClaim(Request $request) {
		return Trip::saveEYatraTripClaim($request);
	}

	public function viewEYatraTripClaim($trip_id) {
		return Trip::getClaimViewData($trip_id);
	}

	public function deleteEYatraTripClaim($trip_id) {
		//CHECK IF AGENT BOOKED TRIP VISITS
		$agent_visits_booked = Visit::where('trip_id', $trip_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
		if ($agent_visits_booked) {
			return response()->json(['success' => false, 'errors' => ['Trip cannot be deleted']]);
		}
		$trip = Trip::where('id', $trip_id)->forceDelete();
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		return response()->json(['success' => true]);
	}

	public function getEligibleAmtBasedonCitycategoryGrade(Request $request) {
		if (!empty($request->city_id) && !empty($request->grade_id) && !empty($request->expense_type_id)) {
			$city_category_id = NCity::where('id', $request->city_id)->where('company_id', Auth::user()->company_id)->first();
			if ($city_category_id) {
				$grade_expense_type = DB::table('grade_expense_type')->where('grade_id', $request->grade_id)->where('expense_type_id', $request->expense_type_id)->where('city_category_id', $city_category_id->category_id)->first();
				if (!$grade_expense_type) {
					$grade_expense_type = '';
				}
			} else {
				$grade_expense_type = '';
			}

		} else {
			$grade_expense_type = '';
		}
		return response()->json(['grade_expense_type' => $grade_expense_type]);
	}

	public function getEligibleAmtBasedonCitycategoryGradeStaytype(Request $request) {
		if (!empty($request->city_id) && !empty($request->grade_id) && !empty($request->expense_type_id) && !empty($request->stay_type_id)) {
			$city_category_id = NCity::where('id', $request->city_id)->where('company_id', Auth::user()->company_id)->first();
			if ($city_category_id) {
				$grade_expense_type = DB::table('grade_expense_type')->where('grade_id', $request->grade_id)->where('expense_type_id', $request->expense_type_id)->where('city_category_id', $city_category_id->category_id)->first();
				if ($grade_expense_type) {
					if ($request->stay_type_id == 3341) {
						//STAY TYPE HOME

						//GET GRADE STAY TYPE
						$grade_stay_type = DB::table('grade_advanced_eligibility')->where('grade_id', $request->grade_id)->first();
						if ($grade_stay_type) {
							if ($grade_stay_type->stay_type_disc) {
								$percentage = (int) $grade_stay_type->stay_type_disc;
								$totalWidth = $grade_expense_type->eligible_amount;
								$eligible_amount = ($percentage / 100) * $totalWidth;
							} else {
								$eligible_amount = $grade_expense_type->eligible_amount;
							}
						} else {
							$eligible_amount = $grade_expense_type->eligible_amount;
						}

					} else {
						$eligible_amount = $grade_expense_type->eligible_amount;
					}
				} else {
					$eligible_amount = '0.00';
				}
			} else {
				$eligible_amount = '0.00';
			}

		} else {
			$eligible_amount = '0.00';
		}
		$eligible_amount = number_format((float) $eligible_amount, 2, '.', '');
		return response()->json(['eligible_amount' => $eligible_amount]);
	}

	//GET TRAVEL MODE CATEGORY STATUS TO CHECK IF IT IS NO VEHICLE CLAIM
	public function getVisitTrnasportModeClaimStatus(Request $request) {
		return Trip::getVisitTrnasportModeClaimStatus($request);
	}

	// Function to get all the dates in given range
	public static function getDatesFromRange($start, $end, $format = 'd-m-Y') {
		// Declare an empty array
		$array = array();
		// Variable that store the date interval
		// of period 1 day
		$interval = new DateInterval('P1D');
		$realEnd = new DateTime($end);
		$realEnd->add($interval);
		$period = new DatePeriod(new DateTime($start), $interval, $realEnd);
		// Use loop to store date into array
		foreach ($period as $date) {
			$array[] = $date->format($format);
		}
		// Return the array elements
		return $array;
	}

	public function eyatraTripExpenseData(Request $request) {
		// $lodgings = array();
		$travelled_cities_with_dates = array();
		$lodge_cities = array();
		// $boarding_to_date = '';
		if (!empty($request->visits)) {
			foreach ($request->visits as $visit_key => $visit) {
				$city_category_id = NCity::where('id', $visit['to_city_id'])->where('company_id', Auth::user()->company_id)->first();
				if ($city_category_id) {
					$lodging_expense_type = DB::table('grade_expense_type')->where('grade_id', $visit['grade_id'])->where('expense_type_id', 3001)->where('city_category_id', $city_category_id->category_id)->first();
					$board_expense_type = DB::table('grade_expense_type')->where('grade_id', $visit['grade_id'])->where('expense_type_id', 3002)->where('city_category_id', $city_category_id->category_id)->first();
					$local_travel_expense_type = DB::table('grade_expense_type')->where('grade_id', $visit['grade_id'])->where('expense_type_id', 3003)->where('city_category_id', $city_category_id->category_id)->first();
				}
				$loadge_eligible_amount = $lodging_expense_type ? $lodging_expense_type->eligible_amount : '0.00';
				$board_eligible_amount = $board_expense_type ? $board_expense_type->eligible_amount : '0.00';
				$local_travel_eligible_amount = $local_travel_expense_type ? $local_travel_expense_type->eligible_amount : '0.00';

				$lodge_cities[$visit_key]['city'] = $visit['to_city'];
				$lodge_cities[$visit_key]['city_id'] = $visit['to_city_id'];
				$lodge_cities[$visit_key]['loadge_eligible_amount'] = $loadge_eligible_amount;
				// $next = $visit_key;
				// $next++;
				// $lodgings[$visit_key]['city'] = $visit['to_city'];
				// $lodgings[$visit_key]['checkin_enable'] = $visit['arrival_date'];
				// if (isset($request->visits[$next])) {
				// 	// $lodgings[$visit_key]['checkout_disable'] = $request->visits[$next]['departure_date'];
				// 	$boarding_to_date = $request->visits[$next]['arrival_date'];
				// } else {
				// 	// $lodgings[$visit_key]['checkout_disable'] = $visit['arrival_date'];
				// 	$boarding_to_date = $visit['arrival_date'];
				// }
				$range = Trip::getDatesFromRange($visit['departure_date'], $visit['arrival_date']);
				if (!empty($range)) {
					foreach ($range as $range_key => $range_val) {
						$travelled_cities_with_dates[$visit_key][$range_key]['city'] = $visit['to_city'];
						$travelled_cities_with_dates[$visit_key][$range_key]['city_id'] = $visit['to_city_id'];
						$travelled_cities_with_dates[$visit_key][$range_key]['date'] = $range_val;
						$travelled_cities_with_dates[$visit_key][$range_key]['board_eligible_amount'] = $board_eligible_amount;
						$travelled_cities_with_dates[$visit_key][$range_key]['local_travel_eligible_amount'] = $local_travel_eligible_amount;
					}
				}
			}
		} else {
			$travelled_cities_with_dates = array();
			$lodge_cities = array();
		}
		return response()->json(['travelled_cities_with_dates' => $travelled_cities_with_dates, 'lodge_cities' => $lodge_cities]);
	}
	public function calculateLodgingDays(Request $r) {
		// dd($r->all());
		$visit_start_date = date('Y-m-d', strtotime($r->visit_start_date));
		$visit_end_date = date('Y-m-d', strtotime($r->visit_end_date));
		$date_range = Trip::getDatesFromRange($visit_start_date, $visit_end_date);
		$lodging_dates_list = [];
		if (!empty($date_range)) {
			$lodging_dates_list[0]['id'] = '';
			$lodging_dates_list[0]['name'] = 'Select Date';
			foreach ($date_range as $range_key => $range_val) {
				$range_key++;
				$lodging_dates_list[$range_key]['id'] = $range_val;
				$lodging_dates_list[$range_key]['name'] = $range_val;
			}
		}

		return response()->json(['success' => true, 'lodging_dates_list' => $lodging_dates_list]);
	}

}
