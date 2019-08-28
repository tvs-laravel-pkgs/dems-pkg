<?php

namespace Uitoux\EYatra;

use Auth;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use DB;
use Entrust;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class Trip extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'number',
		'employee_id',
		'purpose_id',
		'description',
		'status_id',
		'advance_received',
		'claim_amount',
		'claimed_date',
		'paid_amount',
		'payment_date',
		'created_by',
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function visits() {
		return $this->hasMany('Uitoux\EYatra\Visit');
	}

	public function selfVisits() {
		return $this->hasMany('Uitoux\EYatra\Visit')->where('booking_method_id', 3040); //Employee visits
	}
	public function agentVisits() {
		return $this->hasMany('Uitoux\EYatra\Visit')->where('booking_method_id', 3042);
	}

	public function advanceRequestPayment() {
		return $this->hasOne('Uitoux\EYatra\Payment', 'entity_id')->where('payment_of_id', 3250); //Employee Advance Claim
	}

	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}

	public function purpose() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'purpose_id');
	}

	public function status() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'status_id');
	}

	public function advanceRequestStatus() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'advance_request_approval_status_id');
	}

	public function lodgings() {
		return $this->hasMany('Uitoux\EYatra\Lodging');
	}

	public function boardings() {
		return $this->hasMany('Uitoux\EYatra\Boarding');
	}

	public function localTravels() {
		return $this->hasMany('Uitoux\EYatra\LocalTravel');
	}

	public static function create($employee, $trip_number, $faker, $trip_status_id, $admin) {
		$trip = new Trip();
		$trip->employee_id = $employee->id;
		$trip->number = 'TRP' . $trip_number++;
		$trip->purpose_id = $employee->grade->tripPurposes()->inRandomOrder()->first()->id;
		$trip->description = $faker->sentence;
		$trip->manager_id = $employee->reporting_to_id;
		$trip->status_id = $trip_status_id; //NEW
		$trip->advance_received = $faker->randomElement([0, 500, 100, 1500, 2000]);
		$trip->created_by = $admin->id;
		$trip->save();
		return $trip;

	}

	public static function saveTrip($request) {
		try {
			//validation
			$validator = Validator::make($request->all(), [
				'purpose_id' => [
					'required',
				],
				'visits' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation Errors',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$trip = new Trip;
				$trip->created_by = Auth::user()->id;
				$trip->created_at = Carbon::now();
				$trip->updated_at = NULL;

			} else {
				$trip = Trip::find($request->id);

				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();

				$trip->visits()->sync([]);

			}
			if ($request->advance_received) {
				$trip->advance_received = $request->advance_received;
				$trip->advance_request_approval_status_id = 3260;
			}
			$trip->fill($request->all());
			$trip->number = 'TRP' . rand();
			$trip->employee_id = Auth::user()->entity->id;
			// dd(Auth::user(), );
			$trip->manager_id = Auth::user()->entity->reporting_to_id;
			$trip->status_id = 3021; //NEW
			$trip->save();

			$trip->number = 'TRP' . $trip->id;
			$trip->save();

			//SAVING VISITS
			if ($request->visits) {
				$visit_count = count($request->visits);
				$i = 0;
				foreach ($request->visits as $key => $visit_data) {
					//if no agent found display visit count
					// dd(Auth::user()->entity->outlet->address);
					$visit_count = $i + 1;
					if ($i == 0) {
						$from_city_id = Auth::user()->entity->outlet->address->city->id;
					} else {
						$previous_value = $request->visits[$key - 1];
						$from_city_id = $previous_value['to_city_id'];
					}
					$visit = new Visit;
					$visit->fill($visit_data);
					// dump($visit_data['date']);
					// dump(Carbon::createFromFormat('d/m/Y', $visit_data['date']));
					$visit->date = date('Y-m-d', strtotime($visit_data['date']));
					// dd($visit);
					$visit->from_city_id = $from_city_id;
					$visit->trip_id = $trip->id;
					//booking_method_name - changed for API - Dont revert - ABDUL
					$visit->booking_method_id = $visit_data['booking_method_name'] == 'Self' ? 3040 : 3042;
					$visit->booking_status_id = 3060; //PENDING
					$visit->status_id = 3220; //NEW
					$visit->manager_verification_status_id = 3080; //NEW
					if ($visit_data['booking_method_name'] == 'Agent') {
						$state = $trip->employee->outlet->address->city->state;

						$agent = $state->agents()->where('company_id', Auth::user()->company_id)->withPivot('travel_mode_id')->where('travel_mode_id', $visit_data['travel_mode_id'])->first();

						if (!$agent) {
							return response()->json(['success' => false, 'errors' => ['No agent found for visit - ' . $visit_count]]);
						}
						$visit->agent_id = $agent->id;
					}
					$visit->save();
					$i++;
				}
			}
			DB::commit();
			return response()->json(['success' => true, 'message' => 'Trip saved successfully!', 'trip' => $trip]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public static function getViewData($trip_id) {
		$data = [];
		$trip = Trip::with([
			'visits' => function ($q) {
				$q->orderBy('visits.id');
			},
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'purpose',
			'status',
		])
			->find($trip_id);
		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
			$data['errors'] = ['Trip not found'];
			return response()->json($data);
		}

		if (!Entrust::can('view-all-trips') && $trip->employee_id != Auth::user()->entity_id) {
			$data['success'] = false;
			$data['message'] = 'Trip belongs to you';
			$data['errors'] = ['Trip belongs to you'];
			return response()->json($data);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.date),"%d/%m/%Y") as end_date'))->first();
		$days = $trip->visits()->select(DB::raw('DATEDIFF(MAX(visits.date),MIN(visits.date))+1 as days'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $end_date->end_date;
		$trip->days = $days->days;
		$trip->purpose_name = $trip->purpose->name;
		$trip->status_name = $trip->status->name;
		$data['trip'] = $trip;
		// dd($trip);
		$data['success'] = true;
		return response()->json($data);

	}

	public static function getTripFormData($trip_id) {
		$data = [];
		if (!$trip_id) {
			$data['action'] = 'New';
			$trip = new Trip;
			$visit = new Visit;
			//Changed for API. dont revert. - Abdul
			$visit->booking_method = new Config(['name' => 'Self']);
			$trip->visits = [$visit];
			$data['success'] = true;
		} else {
			$data['action'] = 'Edit';
			$trip = Trip::find($trip_id);
			if (!$trip) {
				$data['success'] = false;
				$data['message'] = 'Trip not found';
			}
		}
		$grade = Auth::user()->entity;
		$grade_eligibility = DB::table('grade_advanced_eligibility')->select('advanced_eligibility')->where('grade_id', $grade->grade_id)->first();
		if ($grade_eligibility) {
			$data['advance_eligibility'] = $grade_eligibility->advanced_eligibility;
		} else {
			$data['advance_eligibility'] = '';
		}

		$data['extras'] = [
			// 'purpose_list' => Entity::uiPurposeList(),
			'purpose_list' => DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get(),
			// 'travel_mode_list' => Entity::uiTravelModeList(),
			'travel_mode_list' => DB::table('grade_travel_mode')->select('travel_mode_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_travel_mode.travel_mode_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get(),
			'city_list' => NCity::getList(),
			'employee_city' => Auth::user()->entity->outlet->address->city,
		];
		$data['trip'] = $trip;

		return response()->json($data);
	}

	public static function getEmployeeList($request) {
		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.date),"%d/%m/%Y") as end_date'),
				//Changed to purpose_name. do not revert - Abdul
				'purpose.name as purpose_name',
				'trips.advance_received',
				'status.name as status_name'
			)
			->where('e.company_id', Auth::user()->company_id)
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
		;
		if (!Entrust::can('view-all-trips')) {
			$trips->where('trips.employee_id', Auth::user()->entity_id);
		}

		//FILTERS
		if ($request->number) {
			$trips->where('trips.number', 'like', '%' . $request->number . '%');
		}
		if ($request->from_date && $request->to_date) {
			$trips->where('v.date', '>=', $request->from_date);
			$trips->where('v.date', '<=', $request->to_date);
		} else {
			$today = Carbon::today();
			$from_date = $today->copy()->subMonths(3);
			$to_date = $today->copy()->addMonths(3);
			$trips->where('v.date', '>=', $from_date);
			$trips->where('v.date', '<=', $to_date);
		}

		if ($request->status_ids && count($request->status_ids) > 0) {
			$trips->whereIn('trips.status_id', $request->status_ids);
		} else {
			$trips->whereNotIn('trips.status_id', [3026]);
		}
		if ($request->purpose_ids && count($request->purpose_ids) > 0) {
			$trips->whereIn('trips.purpose_id', $request->purpose_ids);
		}
		if ($request->from_city_id) {
			$trips->whereIn('v.from_city_id', $request->from_city_id);
		}
		if ($request->to_city_id) {
			$trips->whereIn('v.to_city_id', $request->to_city_id);
		}
		return $trips;
	}

	public static function getVerficationPendingList($r) {
		/*if(isset($r->period))
			{
				$date = explode(' to ', $r->period);
				$from_date = $date[0];
				$to_date = $date[1];
				dd($from_date,$to_date);
				$from_date = date('Y-m-d', strtotime($from_date));
				$to_date = date('Y-m-d', strtotime($to_date));
		*/

		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.date),"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
				'trips.created_at',
				//DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y") as created_at'),
				'status.name as status'

			)
			->where('trips.status_id', 3021) //MANAGER APPROVAL PENDING
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
			->orderBy('trips.status_id', 'desc')
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
					$query->where("status.id", $r->get('status_id'))->orWhere(DB::raw("-1"), $r->get('status_id'));
				}
			})
		/*->where(function ($query) use ($r) {
				if ($r->get('period')) {
					$query->whereDate('v.date',">=",$from_date)->whereDate('v.date',"<=",$to_date);

				}
			})*/
		;

		if (!Entrust::can('trip-verification-all')) {
			$trips->where('trips.manager_id', Auth::user()->entity_id);
		}

		return $trips;
	}

	// public static function saveTripVerification($r) {
	// 	$trip = Trip::find($r->trip_id);
	// 	if (!$trip) {
	// 		return response()->json(['success' => false, 'errors' => ['Trip not found']]);
	// 	}

	// 	if (!Entrust::can('trip-verification-all') && $trip->manager_id != Auth::user()->entity_id) {
	// 		return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
	// 	}

	// 	$trip->status_id = 3021;
	// 	$trip->save();

	// 	$trip->visits()->update(['manager_verification_status_id' => 3080]);
	// 	return response()->json(['success' => true]);
	// }

	public static function approveTrip($r) {
		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->status_id = 3028;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3081]);
		return response()->json(['success' => true]);
	}

	public static function rejectTrip($r) {
		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3022;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3082]);
		return response()->json(['success' => true]);
	}

	public static function getClaimFormData($trip_id) {
		// if (!$trip_id) {
		// 	$this->data['success'] = false;
		// 	$this->data['message'] = 'Trip not found';
		// 	$this->data['employee'] = [];
		// } else {
		$data = [];
		$trip = Trip::with(
			['visits' => function ($q) {
				$q->orderBy('id', 'asc');
			},
				'visits.fromCity',
				'visits.toCity',
				'visits.travelMode',
				'visits.bookingMethod',
				'visits.bookingStatus',
				'visits.agent',
				'visits.status',
				'visits.managerVerificationStatus',
				'employee',
				'employee.user',
				'employee.tripEmployeeClaim' => function ($q) use ($trip_id) {
					$q->where('trip_id', $trip_id);
				},
				'purpose',
				'status',
				'selfVisits' => function ($q) {
					$q->orderBy('id', 'asc');
				},
				'lodgings',
				'lodgings.city',
				'boardings',
				'boardings.city',
				'localTravels',
				'localTravels.city',
				'selfVisits.fromCity',
				'selfVisits.toCity',
				'selfVisits.travelMode',
				'selfVisits.bookingMethod',
				'selfVisits.selfBooking',
				'selfVisits.agent',
				'selfVisits.status',
				'selfVisits.attachments',

			])->find($trip_id);
		// dd($trip);
		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
		}
		if (count($trip->lodgings) > 0) {
			$data['action'] = 'Edit';
			$travelled_cities_with_dates = array();
			$lodge_cities = array();
		} else {
			$data['action'] = 'Add';

			//EXPENSE DATAS CITY AND DATE WISE
			// $lodgings = array();
			$travelled_cities_with_dates = array();
			$lodge_cities = array();
			// $boarding_to_date = '';
			if (!empty($trip->selfVisits)) {
				foreach ($trip->selfVisits as $visit_key => $visit) {
					$city_category_id = NCity::where('id', $visit->to_city_id)->first();
					$grade_id = $trip->employee ? $trip->employee->grade_id : '';
					$lodging_expense_type = DB::table('grade_expense_type')->where('grade_id', $grade_id)->where('expense_type_id', 3001)->where('city_category_id', $city_category_id->category_id)->first();
					$board_expense_type = DB::table('grade_expense_type')->where('grade_id', $grade_id)->where('expense_type_id', 3002)->where('city_category_id', $city_category_id->category_id)->first();
					$local_travel_expense_type = DB::table('grade_expense_type')->where('grade_id', $grade_id)->where('expense_type_id', 3003)->where('city_category_id', $city_category_id->category_id)->first();
					$loadge_eligible_amount = $lodging_expense_type ? $lodging_expense_type->eligible_amount : '0.00';
					$board_eligible_amount = $board_expense_type ? $board_expense_type->eligible_amount : '0.00';
					$local_travel_eligible_amount = $local_travel_expense_type ? $local_travel_expense_type->eligible_amount : '0.00';

					$lodge_cities[$visit_key]['city'] = $visit->toCity ? $visit->toCity->name : '';
					$lodge_cities[$visit_key]['city_id'] = $visit->to_city_id;
					$lodge_cities[$visit_key]['loadge_eligible_amount'] = $loadge_eligible_amount;

					$next = $visit_key;
					$next++;
					// $lodgings[$visit_key]['city'] = $visit['to_city'];
					// $lodgings[$visit_key]['checkin_enable'] = $visit['arrival_date'];
					if (isset($trip->selfVisits[$next])) {
						// $lodgings[$visit_key]['checkout_disable'] = $request->visits[$next]['departure_date'];
						$next_departure_date = $trip->selfVisits[$next]->departure_date;
					} else {
						// $lodgings[$visit_key]['checkout_disable'] = $visit['arrival_date'];
						$next_departure_date = $visit->departure_date;
					}

					$range = Trip::getDatesFromRange($visit->departure_date, $next_departure_date);
					if (!empty($range)) {
						foreach ($range as $range_key => $range_val) {
							$travelled_cities_with_dates[$visit_key][$range_key]['city'] = $visit->toCity ? $visit->toCity->name : '';
							$travelled_cities_with_dates[$visit_key][$range_key]['city_id'] = $visit->to_city_id;
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
		}
		$data['travelled_cities_with_dates'] = $travelled_cities_with_dates;
		$data['lodge_cities'] = $lodge_cities;

		$to_cities = Visit::where('trip_id', $trip_id)->pluck('to_city_id')->toArray();
		$data['success'] = true;

		$data['employee'] = $employee = Employee::select('users.name as name', 'employees.code as code', 'designations.name as designation', 'entities.name as grade', 'employees.grade_id', 'employees.id')
			->leftjoin('designations', 'designations.id', 'employees.designation_id')
			->leftjoin('users', 'users.entity_id', 'employees.id')
			->leftjoin('entities', 'entities.id', 'employees.grade_id')
			->where('employees.id', $trip->employee_id)
			->where('users.user_type_id', 3121)->first();

		$travel_cities = Visit::leftjoin('ncities as cities', 'visits.to_city_id', 'cities.id')
			->where('visits.trip_id', $trip->id)->pluck('cities.name')->toArray();
		$data['travel_cities'] = !empty($travel_cities) ? trim(implode(', ', $travel_cities)) : '--';

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.date),"%d/%m/%Y") as end_date'))->first();
		$days = $trip->visits()->select(DB::raw('DATEDIFF(MAX(visits.date),MIN(visits.date))+1 as days'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $end_date->end_date;
		$trip->days = $days->days;

		//DONT REVERT - ABDUL
		$trip->cities = $data['cities'] = count($travel_cities) > 0 ? trim(implode(', ', $travel_cities)) : '--';
		$data['travel_dates'] = $travel_dates = Visit::select(DB::raw('MAX(DATE_FORMAT(visits.arrival_date,"%d/%m/%Y")) as max_date'), DB::raw('MIN(DATE_FORMAT(visits.departure_date,"%d/%m/%Y")) as min_date'))->where('visits.trip_id', $trip->id)->first();
		// }

		if (!empty($to_cities)) {
			$city_list = collect(NCity::select('id', 'name')->whereIn('id', $to_cities)->get()->prepend(['id' => '', 'name' => 'Select City']));
		} else {
			$city_list = [];
		}
		$booking_type_list = collect(Config::getBookingTypeTypeList()->prepend(['id' => '', 'name' => 'Select Booked By']));
		$purpose_list = collect(Entity::uiPurposeList()->prepend(['id' => -1, 'name' => 'Select Purpose']));
		$travel_mode_list = collect(Entity::uiTravelModeList()->prepend(['id' => -1, 'name' => 'Select Travel Mode']));
		$stay_type_list = collect(Entity::getLodgeStayTypeList()->prepend(['id' => -1, 'name' => 'Select Stay Type']));

		$data['extras'] = [
			'purpose_list' => $purpose_list,
			'travel_mode_list' => $travel_mode_list,
			'city_list' => $city_list,
			'stay_type_list' => $stay_type_list,
			'booking_type_list' => $booking_type_list,
		];
		$data['trip'] = $trip;

		return response()->json($data);
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
}
