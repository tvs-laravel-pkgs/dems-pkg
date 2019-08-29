<?php

namespace Uitoux\EYatra;

// use App\Mail\TripNotificationMail;
use App\User;
use Auth;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use DB;
use Entrust;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mail;
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

	public function cliam() {
		return $this->hasOne('Uitoux\EYatra\EmployeeClaim');
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
				$activity['activity'] = "add";
			} else {
				$trip = Trip::find($request->id);

				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();

				$trip->visits()->sync([]);
				$activity['activity'] = "edit";

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
			$activity['entity_id'] = $trip->id;
			$activity['entity_type'] = 'trip';
			$activity['details'] = NULL;
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
					// $visit->date = date('Y-m-d', strtotime($visit_data['date']));
					$visit->departure_date = date('Y-m-d', strtotime($visit_data['date']));
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
							return response()->json(['success' => false, 'errors' => ['No agent found for visit - ' . $visit_count], 'message' => 'No agent found for visit - ' . $visit_count]);
						}
						$visit->agent_id = $agent->id;
					}
					$visit->save();
					$i++;
				}
			}
			if (!$request->id) {
				// self::sendTripNotificationMail($trip);
			}
			// $activity_log = ActivityLog::saveLog($activity);
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
			'employee.user',
			'employee.designation',
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

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		$days = $trip->visits()->select(DB::raw('DATEDIFF(MAX(visits.departure_date),MIN(visits.departure_date))+1 as days'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $end_date->end_date;
		$trip->days = $days->days;
		$trip->purpose_name = $trip->purpose->name;
		$trip->status_name = $trip->status->name;
		$data['trip'] = $trip;
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
			'purpose_list' => DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Purpose']),
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
			->join('users as u', 'u.entity_id', 'e.id')
			->leftJoin('ey_employee_claims as claim', 'claim.trip_id', 'trips.id')

			->select(
				'trips.id',
				'trips.number',
				DB::raw('CONCAT(u.name," ( ",e.code," ) ") as ecode'),
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.date),"%d/%m/%Y") as end_date'),
				DB::raw('FORMAT(claim.total_amount,2) as claim_amount'),
				//Changed to purpose_name. do not revert - Abdul
				'purpose.name as purpose_name',
				'trips.advance_received',
				'status.name as status_name',
				DB::raw('DATE_FORMAT(MAX(trips.created_at),"%d/%m/%Y %h:%i %p") as date')
			)
			->where('u.user_type_id', 3121)
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
			$trips->where('v.departure_date', '>=', $request->from_date);
			$trips->where('v.departure_date', '<=', $request->to_date);
		} else {
			$today = Carbon::today();
			$from_date = $today->copy()->subMonths(3);
			$to_date = $today->copy()->addMonths(3);
			$trips->where('v.departure_date', '>=', $from_date);
			$trips->where('v.departure_date', '<=', $to_date);
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
			->leftJoin('users', 'users.entity_id', 'e.id')
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
				'trips.created_at',
				//DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y") as created_at'),
				'status.name as status'

			)
			->where('users.user_type_id', 3121)
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
		if (!Entrust::can('verify-all-trips')) {
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
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = NULL;
		$activity['activity'] = "approve";
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);
		$trip->visits()->update(['manager_verification_status_id' => 3081]);
		return response()->json(['success' => true, 'message' => 'Trip approved successfully!']);
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
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['activity'] = "reject";
		$activity['details'] = $r->remarks;
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);

		$trip->visits()->update(['manager_verification_status_id' => 3082]);
		return response()->json(['success' => true, 'message' => 'Trip rejected successfully!']);
	}

	public static function getClaimFormData($trip_id) {
		// if (!$trip_id) {
		// 	$data['success'] = false;
		// 	$data['message'] = 'Trip not found';
		// 	$data['employee'] = [];
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

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		$days = $trip->visits()->select(DB::raw('DATEDIFF(MAX(visits.departure_date),MIN(visits.departure_date))+1 as days'))->first();
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
		$local_travel_mode_list = collect(Entity::uiLocaTravelModeList()->prepend(['id' => -1, 'name' => 'Select Local Travel Mode']));
		$stay_type_list = collect(Config::getLodgeStayTypeList()->prepend(['id' => -1, 'name' => 'Select Stay Type']));

		$data['extras'] = [
			'purpose_list' => $purpose_list,
			'travel_mode_list' => $travel_mode_list,
			'local_travel_mode_list' => $local_travel_mode_list,
			'city_list' => $city_list,
			'stay_type_list' => $stay_type_list,
			'booking_type_list' => $booking_type_list,
		];
		$data['trip'] = $trip;

		return response()->json($data);
	}

	public static function getFilterData() {
		$data = [];
		$data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)
				->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 501)->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		$data['success'] = true;
		//dd($data);
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

	public static function getClaimViewData($trip_id) {
		$data = [];
		if (!$trip_id) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
			return response()->json($data);
		}

		$trip = Trip::with(
			'advanceRequestStatus',
			'employee',
			'employee.user',
			'employee.grade',
			'employee.designation',
			'employee.reportingTo',
			'employee.outlet',
			'employee.Sbu',
			'employee.Sbu.lob',
			'selfVisits',
			'purpose',
			'lodgings',
			'lodgings.city',
			'lodgings.stateType',
			'lodgings.attachments',
			'boardings',
			'boardings.city',
			'boardings.attachments',
			'localTravels',
			'localTravels.fromCity',
			'localTravels.toCity',
			'localTravels.travelMode',
			'localTravels.attachments',
			'selfVisits.fromCity',
			'selfVisits.toCity',
			'selfVisits.travelMode',
			'selfVisits.bookingMethod',
			'selfVisits.selfBooking',
			'selfVisits.agent',
			'selfVisits.status',
			'selfVisits.attachments'
		)->find($trip_id);

		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
		}
		$travel_cities = Visit::leftjoin('ncities as cities', 'visits.to_city_id', 'cities.id')
			->where('visits.trip_id', $trip->id)->pluck('cities.name')->toArray();

		$transport_total = Visit::select(
			DB::raw('COALESCE(SUM(visit_bookings.amount), 0.00) as amount'),
			DB::raw('COALESCE(SUM(visit_bookings.tax), 0.00) as tax')
		)
			->leftjoin('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
			->where('visits.trip_id', $trip_id)
			->groupby('visits.id')
			->first();
		$transport_total_amount = $transport_total ? $transport_total->amount : 0.00;
		$transport_total_tax = $transport_total ? $transport_total->tax : 0.00;
		$data['transport_total_amount'] = $transport_total_amount;

		$lodging_total = Lodging::select(
			DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
			DB::raw('COALESCE(SUM(tax), 0.00) as tax')
		)
			->where('trip_id', $trip_id)
			->groupby('trip_id')
			->first();
		$lodging_total_amount = $lodging_total ? $lodging_total->amount : 0.00;
		$lodging_total_tax = $lodging_total ? $lodging_total->tax : 0.00;
		$data['lodging_total_amount'] = $lodging_total_amount;

		$boardings_total = Boarding::select(
			DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
			DB::raw('COALESCE(SUM(tax), 0.00) as tax')
		)
			->where('trip_id', $trip_id)
			->groupby('trip_id')
			->first();
		$boardings_total_amount = $boardings_total ? $boardings_total->amount : 0.00;
		$boardings_total_tax = $boardings_total ? $boardings_total->tax : 0.00;
		$data['boardings_total_amount'] = $boardings_total_amount;

		$local_travels_total = LocalTravel::select(
			DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
			DB::raw('COALESCE(SUM(tax), 0.00) as tax')
		)
			->where('trip_id', $trip_id)
			->groupby('trip_id')
			->first();
		$local_travels_total_amount = $local_travels_total ? $local_travels_total->amount : 0.00;
		$local_travels_total_tax = $local_travels_total ? $local_travels_total->tax : 0.00;
		$data['local_travels_total_amount'] = $local_travels_total_amount;

		$total_amount = $transport_total_amount + $transport_total_tax + $lodging_total_amount + $lodging_total_tax + $boardings_total_amount + $boardings_total_tax + $local_travels_total_amount + $local_travels_total_tax;
		$data['total_amount'] = number_format($total_amount, 2, '.', '');
		$data['travel_cities'] = !empty($travel_cities) ? trim(implode(', ', $travel_cities)) : '--';
		$data['travel_dates'] = $travel_dates = Visit::select(DB::raw('MAX(DATE_FORMAT(visits.arrival_date,"%d/%m/%Y")) as max_date'), DB::raw('MIN(DATE_FORMAT(visits.departure_date,"%d/%m/%Y")) as min_date'))->where('visits.trip_id', $trip->id)->first();
		$data['success'] = true;

		$data['trip'] = $trip;

		return response()->json($data);
	}
	public static function sendTripNotificationMail($trip) {
		try {

			$trip_id = $trip->id;
			$trip_visits = $trip->visits;
			if ($trip_visits) {
				//agent Booking Count checking
				$visit_agents = Visit::select(
					'visits.id',
					'trips.id as trip_id',
					'users.name as employee_name',
					DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
					'fromcity.name as fromcity_name',
					'tocity.name as tocity_name',
					'travel_modes.name as travel_mode_name',
					'booking_modes.name as booking_method_name'
				)
					->join('trips', 'trips.id', 'visits.trip_id')
					->leftjoin('users', 'trips.employee_id', 'users.id')
					->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
					->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
					->join('entities as travel_modes', 'travel_modes.id', 'visits.travel_mode_id')
					->join('configs as booking_modes', 'booking_modes.id', 'visits.booking_method_id')
					->where('booking_method_id', 3042)->where('trip_id', $trip_id)
					->get();
				$visit_agent_count = $visit_agents->count();
				//dd($visit_agent_count);
				if ($visit_agent_count > 0) {
					// Agent Mail Trigger
					foreach ($visit_agents as $key => $visit_agent) {
						$arr['from_mail'] = 'saravanan@uitoux.in';
						$arr['from_name'] = 'Agent';
						$arr['to_email'] = 'parthiban@uitoux.in';
						$arr['to_name'] = 'parthiban';
						//dd($user_details_cc['email']);
						$arr['subject'] = 'Employee ticket booking notification';
						$arr['body'] = 'Employee ticket booking notification';
						$arr['visits'] = $visit_agent;
						$arr['type'] = 1;
						$MailInstance = new TripNotificationMail($arr);
						$Mail = Mail::send($MailInstance);
					}
				}
				// Manager mail trigger
				$visit_manager = Visit::select(
					'visits.id',
					'trips.id as trip_id',
					'users.name as employee_name',
					DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
					'fromcity.name as fromcity_name',
					'tocity.name as tocity_name',
					'travel_modes.name as travel_mode_name',
					'booking_modes.name as booking_method_name'
				)
					->join('trips', 'trips.id', 'visits.trip_id')
					->leftjoin('users', 'trips.employee_id', 'users.id')
					->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
					->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
					->join('entities as travel_modes', 'travel_modes.id', 'visits.travel_mode_id')
					->join('configs as booking_modes', 'booking_modes.id', 'visits.booking_method_id')
					->where('visits.trip_id', $trip_id)
					->get();
				//dd($visit_manager);
				if ($visit_manager) {
					$arr['from_mail'] = 'saravanan@uitoux.in';
					$arr['from_name'] = 'Manager';
					$arr['to_email'] = 'saravanan@uitoux.in';
					$arr['to_name'] = 'parthiban';
					//dd($user_details_cc['email']);
					$arr['subject'] = 'Employee ticket booking notification';
					$arr['body'] = 'Employee ticket booking notification';
					$arr['visits'] = $visit_manager;
					$arr['type'] = 2;
					$MailInstance = new TripNotificationMail($arr);
					$Mail = Mail::send($MailInstance);
				}
				// Financier mail trigger
				$visit_financier = Visit::select(
					'visits.id',
					'trips.id as trip_id',
					'trips.advance_received as advance_amount',
					'users.name as employee_name',
					DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
					'fromcity.name as fromcity_name',
					'tocity.name as tocity_name',
					'travel_modes.name as travel_mode_name',
					'booking_modes.name as booking_method_name'
				)
					->join('trips', 'trips.id', 'visits.trip_id')
					->leftjoin('users', 'trips.employee_id', 'users.id')
					->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
					->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
					->join('entities as travel_modes', 'travel_modes.id', 'visits.travel_mode_id')
					->join('configs as booking_modes', 'booking_modes.id', 'visits.booking_method_id')
					->where('visits.trip_id', $trip_id)
					->where('trips.advance_received', '>', 0)
					->get();
				$visit_financier_count = $visit_financier->count();
				if ($visit_financier_count > 0) {
					$arr['from_mail'] = 'saravanan@uitoux.in';
					$arr['from_name'] = 'Financier';
					$arr['to_email'] = 'saravanan@uitoux.in';
					$arr['to_name'] = 'parthiban';
					//dd($user_details_cc['email']);
					$arr['subject'] = 'Employee ticket booking notification';
					$arr['body'] = 'Employee ticket booking notification';
					$arr['visits'] = $visit_financier;
					$arr['type'] = 3;
					$MailInstance = new TripNotificationMail($arr);
					$Mail = Mail::send($MailInstance);
				}
			}
		} catch (Exception $e) {
			return response()->json(['success' => false, 'errors' => ['Error_Message' => $e->getMessage()]]);
		}
	}

}
