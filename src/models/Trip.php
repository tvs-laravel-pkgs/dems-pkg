<?php

namespace Uitoux\EYatra;

//use App\Mail\TripNotificationMail;

use App\Attachment;
use App\Company;
use App\FinancialYear;
use App\SerialNumberGroup;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Mail;
use Session;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\LodgingShareDetail;
use Uitoux\EYatra\LodgingTaxInvoice;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\OperatingStates;
use Uitoux\EYatra\GradeAdvancedEligiblity;
use Uitoux\EYatra\Sbu;
use Validator;
use App\Oracle\OtherTypeTransactionDetail;
use App\Portal;
use Config as dataBaseConfig;


class Trip extends Model {
	use SoftDeletes;

	protected $fillable = [
		// 'id',
		'number',
		'employee_id',
		'purpose_id',
		'description',
		'status_id',
		'claim_amount',
		'claimed_date',
		'paid_amount',
		'payment_date',
		'start_date',
		'end_date',
		'trip_type',
		'created_by',
	];

	public function getStartDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}
	public function getClaimedDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}
	public function getEndDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}

	public function setStartDateAttribute($date) {
		return $this->attributes['start_date'] = empty($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));
	}
	public function setEndDateAttribute($date) {
		return $this->attributes['end_date'] = empty($date) ? date('Y-m-d') : date('Y-m-d', strtotime($date));

	}
	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function visits() {
		return $this->hasMany('Uitoux\EYatra\Visit')->whereNotIn('booking_status_id', [3064])->whereNotIn('status_id', [3229])->orderBy('id');
	}

	public function selfVisits() {
		return $this->hasMany('Uitoux\EYatra\Visit')->where('booking_method_id', 3040)->orderBy('id', 'ASC'); //Employee visits
	}

	public function agentVisits() {
		return $this->hasMany('Uitoux\EYatra\Visit')->where('booking_method_id', 3042)->orderBy('id', 'ASC');
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

	public function transport_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3189)->where('attachment_type_id', 3200);
	}

	public function lodging_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3181)->where('attachment_type_id', 3200);
	}

	public function boarding_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3182)->where('attachment_type_id', 3200);
	}
	public function local_travel_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3183)->where('attachment_type_id', 3200);
	}

	public function google_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3185)->where('attachment_type_id', 3200);
	}

	public function pending_transport_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3189)->where('attachment_type_id', 3200)->where('view_status', 0);
	}
	public function pending_lodging_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3181)->where('attachment_type_id', 3200)->where('view_status', 0);
	}

	public function pending_boarding_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3182)->where('attachment_type_id', 3200)->where('view_status', 0);
	}
	public function pending_local_travel_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3183)->where('attachment_type_id', 3200)->where('view_status', 0);
	}

	public function pending_google_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3185)->where('attachment_type_id', 3200)->where('view_status', 0);
	}
	public function tripAttachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')
			->where('attachment_type_id', 3200)
			->whereIn('attachment_of_id', [3750, 3751, 3752, 3753, 3754, 3755, 3756]);
		// ->where('attachment_of_id', '!=', 3185);
	}
	public function pendingTripAttachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')
			->where('attachment_type_id', 3200)
		// ->where('attachment_of_id', '!=', 3185)
			->whereIn('attachment_of_id', [3750, 3751, 3752, 3753, 3754, 3755, 3756])
			->where('view_status', 0);
	}
	public function managerApprovedTripLog() {
		return $this->hasOne('Uitoux\EYatra\ActivityLog', 'entity_id', 'id')
			->where('entity_type_id', 3300)
			->where('activity_id', 3323)
			->where('details', 'Trip is Approved by Manager')
			->orderBy('date_time', 'DESC');
	}

	public function branch() {
		return $this->belongsTo('Uitoux\EYatra\Outlet', 'outlet_id');
	}

	public static function generate($employee, $trip_number, $faker, $trip_status_id, $admin) {
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
		// dd($request->all());
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

			//EMPLOYEE MOBILE NUMBER AND EMAIL VALIDATION
			$authUser = Auth::user();
			if ($authUser) {
				$employeeData = [];
				$employeeData['mobile_number'] = $authUser->mobile_number;
				$employeeData['email_id'] = $authUser->email;
				$employeeErrorMessages = [
					'mobile_number.required' => 'The employee mobile number is required',
					'mobile_number.digits' => 'The employee mobile number must be 10 digits',
					'email_id.required' => 'The employee email is required',
					'email_id.email' => 'The employee email id must be a valid email address',
				];
				$employeeValidator = Validator::make($employeeData, [
					'mobile_number' => [
						'required',
						'digits:10',
					],
					'email_id' => [
						'required',
						'email',
					],
				], $employeeErrorMessages);
				if ($employeeValidator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $employeeValidator->errors()->all(),
					]);
				}
			}
			$enable_agent_booking_preference = Config::where('id', 3971)->first()->name;

			DB::beginTransaction();
			if (!$request->id) {
				$outlet_id = (isset(Auth::user()->entity->outlet_id) && Auth::user()->entity->outlet_id) ? Auth::user()->entity->outlet_id : null;
				if (!$outlet_id) {
					return response()->json(['success' => false, 'errors' => 'Outlet not found!']);
				}

				$financial_year = getFinancialYear();
				$financial_year_id = FinancialYear::where('from', $financial_year)->pluck('id')->first();
				if (!$financial_year_id) {
					return response()->json(['success' => false, 'errors' => ['Financial Year Not Found']]);
				}

				// Outstation Trip
				$get_request_no = SerialNumberGroup::generateNumber(2, $financial_year_id, $outlet_id);
				if (!$get_request_no['success']) {
					return response()->json(['success' => false, 'errors' => ['Serial Number Not Found']]);
				}

				$number = $get_request_no['number'];

				$trip = new Trip;
				$trip->company_id = Auth::user()->company_id;
				$trip->outlet_id = $outlet_id;
				$trip->number = $number;
				$trip->created_by = Auth::user()->id;
				$trip->created_at = Carbon::now();
				$trip->updated_at = NULL;
				$activity['activity'] = "add";
				$trip->advance_received = $request->advance_received;
				if ($request->advance_received >= 1) {
					$trip->advance_request_approval_status_id = 3260;
				}
			} else {
				$trip = Trip::find($request->id);

				//Check Financier Approve Advance amount
				if ($trip->advance_request_approval_status_id != 3261) {
					$trip->advance_received = $request->advance_received;
					if ($request->advance_received >= 1) {
						$trip->advance_request_approval_status_id = 3260;
					}
				}
				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();
				//$trip->visits()->delete();
				$activity['activity'] = "edit";

			}
			$employee = Employee::where('id', Auth::user()->entity->id)->first();

			$trip->fill($request->all());
			// $trip->number = 'TRP' . rand();
			$trip->employee_id = Auth::user()->entity->id;
			// dd(Auth::user(), );
			$trip->manager_id = Auth::user()->entity->reporting_to_id;
			if ($employee->self_approve == 1) {
				$trip->status_id = 3028; //Manager Approved
			} else {
				$trip->status_id = 3021; //Manager Approval Pending
			}
			$trip->rejection_id = NULL;
			$trip->rejection_remarks = NULL;
			$trip->save();

			// $trip->number = 'TRP' . $trip->id;
			$trip->save();
			$activity['entity_id'] = $trip->id;
			$activity['entity_type'] = 'trip';
			$activity['details'] = 'Trip is Added';
			//SAVING VISITS
			//dd($trip);
			//dump($request->visits);
			if ($request->visits) {
				$visit_count = count($request->visits);
				$i = 0;

				//Check Visits booking status pending or booked.If Pending means remove
				$visit = Visit::where('trip_id', $trip->id)->where('booking_status_id', 3060)->forceDelete();
				$booking_methods = ['Self','Agent'];
				foreach ($request->visits as $key => $visit_data) {
					//dump($visit_data);
					if (empty($visit_data['booking_method_name'])) {
						return response()->json([
							'success' => false,
							'errors' => "Booking method preference is required.",
						]);
					}

					if(!in_array($visit_data['booking_method_name'], $booking_methods)){
						return response()->json([
							'success' => false,
							'errors' => "Invalid booking method preference.",
						]);
					}

					if (isset($visit_data['booking_method_name']) && $visit_data['booking_method_name'] == 'Agent' && $enable_agent_booking_preference == 'No') {
						return response()->json([
							'success' => false,
							'errors' => "Agent booking preference option is temporarily unavailable. Kindly proceed the Self booking option."]);
					}

					//if no agent found display visit count
					// dd(Auth::user()->entity->outlet->address);
					$visit_count = $i + 1;
					if ($i == 0) {
						//$from_city_id = Auth::user()->entity->outlet->address->city->id;
						$from_city_id = $visit_data['from_city_id'];
					} else {
						$previous_value = $request->visits[$key - 1];
						$from_city_id = $previous_value['to_city_id'];
					}
					if ($visit_data['from_city_id'] == $visit_data['to_city_id']) {
						return response()->json(['success' => false, 'errors' => "From City and To City should not be same,please choose another To city"]);
					}
					if ($visit_data['id']) {
						$old_visit = Visit::find($visit_data['id']);
						//dump('old_visit id :'.$old_visit->id);
						// if ($visit_data['booking_method_name'] == 'Agent') {

						//check visit booked or not
						$old_visit_booked = Visit::where('id', $visit_data['id'])
							->where('booking_status_id', 3061) //Booked
							->first();

						// dd($old_visit_booked);
						//dump('old_visit_booked :'.$old_visit_booked->id);
						if ($old_visit_booked) {
							$old_visit_detail_check = Visit::where('id', $visit_data['id'])
								->where('from_city_id', $visit_data['from_city_id'])
								->where('to_city_id', $visit_data['to_city_id'])
								->where('other_city', $visit_data['other_city'])
								->where('travel_mode_id', $visit_data['travel_mode_id'])
								->where('booking_method_id', $visit_data['booking_method_name'] == 'Self' ? 3040 : 3042)
								->whereDate('departure_date', date('Y-m-d', strtotime($visit_data['date'])))
								->first();
							if ($old_visit_detail_check) {
								$visit = $old_visit;
							} else {
								$old_visit->booking_status_id = 3061; //Visit Rescheduled
								$old_visit->status_id = 3229; //Visit Rescheduled
								$old_visit->save();
								$visit = new Visit;
								$visit->booking_status_id = 3060; //PENDING
								$visit->status_id = 3220; //NEW
								$visit->manager_verification_status_id = 3080; //NEW
							}
						} else {
							if ($old_visit) {
								$visit = $old_visit;
							} else {
								$visit = new Visit;
							}

							$visit->booking_status_id = 3060; //PENDING
							$visit->status_id = 3220; //NEW
							$visit->manager_verification_status_id = 3080; //NEW
						}
						// } else {
						// 	//Booking Method Self
						// 	$visit = $old_visit;
						// }
						// dd($visit);
					} else {
						$visit = new Visit;
						$visit->booking_status_id = 3060; //PENDING
						$visit->status_id = 3220; //NEW
						$visit->manager_verification_status_id = 3080; //NEW
					}
					// $visit->prefered_departure_time = date('H:i:s', strtotime($visit_data['prefered_departure_time']));
					// $visit->fill($visit_data);
					$visit->trip_id = $trip->id;
					$visit->from_city_id = $from_city_id;
					$visit->to_city_id = $visit_data['to_city_id'];
					$visit->other_city = $visit_data['other_city'] ? $visit_data['other_city'] : NULL;
					$visit->travel_mode_id = $visit_data['travel_mode_id'];
					$visit->trip_mode_id = $visit_data['trip_mode_id'];
					$visit->departure_date = date('Y-m-d', strtotime($visit_data['date']));
					//booking_method_name - changed for API - Dont revert - ABDUL
					$visit->booking_method_id = $visit_data['booking_method_name'] == 'Self' ? 3040 : 3042;
					$visit->prefered_departure_time = $visit_data['booking_method_name'] == 'Self' ? NULL : $visit_data['prefered_departure_time'] ? date('H:i:s', strtotime($visit_data['prefered_departure_time'])) : NULL;
					if ($visit->booking_method_id == 3040) {
						// $visit->self_booking_approval = 1;
						if (isset($visit_data['self_booking_approval'])) {
							$visit->self_booking_approval = $visit_data['self_booking_approval'];
						}
					} else {
						$visit->self_booking_approval = 0;
					}
					if ($visit_data['booking_method_name'] == 'Agent') {
						$state = $trip->employee->outlet->address->city->state;

						$agent = $state->agents()->where('company_id', Auth::user()->company_id)->withPivot('travel_mode_id')->where('travel_mode_id', $visit_data['travel_mode_id'])->first();
						if (!$agent) {
							return response()->json(['success' => false, 'errors' => ['No agent found for visit - ' . $visit_count], 'message' => 'No agent found for visit - ' . $visit_count]);
						}
						$visit->agent_id = $agent->id;
					} else {
						$visit->agent_id = NULL;
					}
					$visit->notes_to_agent = isset($visit_data['notes_to_agent']) ? $visit_data['notes_to_agent'] : NULL;
					$visit->save();
					$i++;
				}
			}

			DB::commit();
			$employee = Employee::where('id', $trip->employee_id)->first();
			$user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();
			$notification = sendnotification($type = 1, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Trip Requested');
			$activity_log = ActivityLog::saveLog($activity);

			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Trip added successfully!', 'trip' => $trip]);
			} else {
				return response()->json(['success' => true, 'message' => 'Trip updated successfully!', 'trip' => $trip]);
			}

		} catch (\Exception $e) {
			DB::rollBack();

			// return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
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
			'visits.travelMode.travelModesCategories',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.agent.user',
			'visits.status',
			'visits.booking',
			'visits.managerVerificationStatus',
			'employee',
			'employee.user',
			'employee.manager',
			'employee.manager.user',
			'employee.user',
			'employee.designation',
			'employee.grade',
			'employee.grade.gradeEligibility',
			'purpose',
			'status',
			'managerApprovedTripLog',
			'managerApprovedTripLog.user',
		])
			->find($trip_id);
		//dd($trip);
		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
			$data['errors'] = ['Trip not found'];
			return response()->json($data);
		}

		$employee = Employee::find($trip->employee_id);
		// $pending_trip = Trip::where('trips.employee_id', Auth::user()->entity_id)
		//               ->where('trips.id','<', $trip->id)
		//               ->whereIn('trips.status_id',[3021,3028])
		//               ->orderBy('trips.id','desc')->first();
		$tripStartDate = date("Y-m-d", strtotime($trip->start_date));
		$pending_trip = Trip::where('trips.employee_id', Auth::user()->entity_id)
			->where('trips.id', '!=', $trip->id)
			->where('trips.start_date', '<=', $tripStartDate)
			->where('trips.id', '<', $trip->id)
			->whereIn('trips.status_id', [3021, 3028])
			->first();
		$pending_trip_status = !!$pending_trip;
		$sbu_name = Sbu::where('id', $trip->employee->sbu_id)->pluck('name')->first();
		$trip->sbu_name = $sbu_name;
		if ((!Entrust::can('view-all-trips') && $trip->employee_id != Auth::user()->entity_id) && $employee->reporting_to_id != Auth::user()->entity_id) {
			$data['success'] = false;
			$data['message'] = 'Trip belongs to you';
			$data['errors'] = ['Trip belongs to you'];
			return response()->json($data);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		$days = Trip::select(DB::raw('DATEDIFF(end_date,start_date)+1 as days'))->where('id', $trip_id)->first();
		$trip->days = $days->days;
		$trip->purpose_name = $trip->purpose->name;
		$trip->status_name = $trip->status->name;
		$current_date = strtotime(date('d-m-Y'));
		$claim_date = $trip->employee->grade ? $trip->employee->grade->gradeEligibility->claim_active_days : 5;

		// $claim_last_date = strtotime("+" . $claim_date . " day", strtotime($trip->end_date));
		$tripEndDate = date("Y-m-d", strtotime($trip->end_date));
		$tripApprovedLog = ApprovalLog::select([
			'id',
			DB::raw('DATE_FORMAT(approved_at,"%d-%m-%Y") as approved_formatted_date'),
			DB::raw('DATE_FORMAT(approved_at,"%Y-%m-%d") as approved_date'),
		])
			->where('type_id', 3581) //Outstation Trip
			->where('approval_type_id', 3600) //Outstation Trip - Manager Approved
			->where('entity_id', $trip->id)
			->first();

		$claim_last_date = strtotime("+" . $claim_date . " day", strtotime($trip->end_date));
		if ($tripApprovedLog && $tripApprovedLog->approved_date > $tripEndDate) {
			$claim_last_date = strtotime("+" . $claim_date . " day", strtotime($tripApprovedLog->approved_formatted_date));
		}

		$trip_start_date = strtotime($trip->start_date);
		$trip_end_date = strtotime($trip->end_date);

		// if ($current_date < $trip_end_date) {
		// 	$data['claim_status'] = 0;
		// } else {
		// 	if ($current_date <= $claim_last_date) {
		// 		$data['claim_status'] = 1;
		// 	} else {
		// 		$data['claim_status'] = 0;
		// 	}
		// }

		$data['claim_status'] = 1;
		if (($current_date >= $trip_start_date) && ($current_date <= $trip_end_date)) {
			$data['claim_status'] = 1;
		}

		if ($current_date > $trip_end_date) {
			if ($current_date <= $claim_last_date) {
				$data['claim_status'] = 1;
			}
		}

		$data['trip'] = $trip;
		$trip->today = Carbon::today()->format('d-m-Y');
		if ($trip->advance_request_approval_status_id) {
			if ($trip->advance_request_approval_status_id == 3260 || $trip->advance_request_approval_status_id == 3262) {
				$trip_reject = 1;
			} else {
				$trip_reject = 0;
			}
		} else {
			$trip_reject = 1;
		}

		$data['trip_reject'] = $trip_reject;
		$data['approval_status'] = Trip::validateAttachment($trip_id);
		$data['pending_trip_status'] = $pending_trip_status;
		$data['pending_trip'] = $pending_trip;
		$data['success'] = true;
		$data['view'] = URL::asset('public/img/content/yatra/table/view.svg');
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
			$trip->trip_type = '';
			$trip->from_city_details = DB::table('ncities')->leftJoin('nstates', 'ncities.state_id', 'nstates.id')->select('ncities.id', DB::raw('CONCAT(ncities.name,"/",nstates.name) as name'))->where('ncities.id', Auth::user()->entity->outlet->address->city_id)->first();
			$trip_advance_amount_edit = 1;
			$trip_advance_amount_employee_edit = 1;
			$data['success'] = true;
		} else {
			$data['action'] = 'Edit';
			$data['success'] = true;

			$trip = Trip::find($trip_id);

			if (!Entrust::can('trip-edit') || (!in_array($trip->status_id, [3021, 3022, 3032]))) {
				$data['success'] = false;
				$data['error'] = 'Not possible to update the Trip details';
				return response()->json($data);
			}

			$trip->visits = $t_visits = $trip->visits;
			//dd($trip->visits);
			foreach ($t_visits as $key => $t_visit) {
				$b_name = Config::where('id', $trip->visits[$key]->booking_method_id)->select('name')->first();
				$trip->visits[$key]->booking_method_name = $b_name->name;
				$key_val = ($key - 1 < 0) ? 0 : $key - 1;
				$trip->visits[$key]->to_city_details = DB::table('ncities')->leftJoin('nstates', 'ncities.state_id', 'nstates.id')
					->select(
						'ncities.id',
						DB::raw('IF(ncities.id=4100,ncities.name,CONCAT(ncities.name," - ",nstates.name)) as name')
						// DB::raw('CONCAT(ncities.name,"-",nstates.name) as name')
					)->where('ncities.id', $trip->visits[$key]->to_city_id)->first();
				$trip->visits[$key]->from_city_details = DB::table('ncities')->leftJoin('nstates', 'ncities.state_id', 'nstates.id')
					->select(
						'ncities.id',
						DB::raw('IF(ncities.id=4100,ncities.name,CONCAT(ncities.name," - ",nstates.name)) as name')
						// DB::raw('CONCAT(ncities.name,"-",nstates.name) as name')
					)->where('ncities.id', $trip->visits[$key_val]->from_city_id)->first();
			}

			//dd($t_visits, $trip->visits, $key_val);

			if (!$trip) {
				$data['success'] = false;
				$data['message'] = 'Trip not found';
			}
			if ($trip->advance_request_approval_status_id) {
				if ($trip->advance_request_approval_status_id == 3260 || $trip->advance_request_approval_status_id == 3262) {
					$trip_advance_amount_edit = 1;
				} else {
					$trip_advance_amount_edit = 0;
				}
			} else {
				$trip_advance_amount_edit = 1;
			}
			if ($trip->status_id) {
				if ($trip->status_id == 3021 || $trip->status_id == 3022 || $trip->status_id == 3032) {
					$trip_advance_amount_employee_edit = 1;
				} else {
					$trip_advance_amount_employee_edit = 0;
				}
			} else {
				$trip_advance_amount_employee_edit = 1;
			}

		}

		$grade = Auth::user()->entity;
		//dd('ss', Auth::user()->id, Auth::user()->entity->outlet, Auth::user()->entity->outlet->address);
		$grade_eligibility = DB::table('grade_advanced_eligibility')->select('advanced_eligibility', 'travel_advance_limit')->where('grade_id', $grade->grade_id)->first();
		if ($grade_eligibility) {
			$data['advance_eligibility'] = $grade_eligibility->advanced_eligibility;
			$data['grade_advance_eligibility_amount'] = $grade_eligibility->travel_advance_limit;

		} else {
			$data['advance_eligibility'] = '';
			$data['grade_advance_eligibility_amount'] = 0;
		}
		//dd(Auth::user()->entity->outlet->address);

		$data['extras'] = [
			// 'purpose_list' => Entity::uiPurposeList(),
			'purpose_list' => DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Purpose']),
			// 'travel_mode_list' => Entity::uiTravelModeList(),
			'travel_mode_list' => DB::table('grade_travel_mode')->select('travel_mode_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_travel_mode.travel_mode_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get(),
			'city_list' => NCity::getList(),
			'employee_city' => Auth::user()->entity->outlet->address->city,
			'frequently_travelled' => Visit::join('ncities', 'ncities.id', 'visits.to_city_id')->leftJoin('nstates', 'ncities.state_id', 'nstates.id')->where('ncities.company_id', Auth::user()->company_id)->select('ncities.id', DB::raw('CONCAT(ncities.name," - ",nstates.name) as name'))->distinct()->limit(10)->get(),
			'claimable_travel_mode_list' => DB::table('travel_mode_category_type')->where('category_id', 3403)->pluck('travel_mode_id'),
			'trip_mode' => collect(Config::select('name', 'id')->where('config_type_id', 548)->get())->prepend(['id' => '-1', 'name' => 'Select Trip Mode']),
		];
		$data['trip'] = $trip;

		$data['trip_advance_amount_edit'] = $trip_advance_amount_edit;
		$data['trip_advance_amount_employee_edit'] = $trip_advance_amount_employee_edit;

		$data['eligible_date'] = $eligible_date = date("Y-m-d", strtotime("-60 days"));
		$data['max_eligible_date'] = $max_eligible_date = date("Y-m-d", strtotime("+90 days"));
		$data['is_self_booking_approval_must'] = Config::where('id', 3972)->first()->name;
		return response()->json($data);
	}

	public static function getEmployeeList($request) {
		// dd(Auth::user()->company_id);
		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->leftjoin('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->join('users as u', 'u.entity_id', 'e.id')
			->leftJoin('ey_employee_claims as claim', 'claim.trip_id', 'trips.id')

			->select(
				'trips.id',
				'trips.updated_at',
				'trips.number',
				DB::raw('CONCAT(u.name," ( ",e.code," ) ") as ecode'),
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'trips.start_date',
				'trips.end_date',
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				// DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				DB::raw('FORMAT(claim.total_amount,2) as claim_amount'),
				//Changed to purpose_name. do not revert - Abdul
				'purpose.name as purpose_name',
				'trips.advance_received',
				'trips.status_id as status_id',
				'status.name as status_name',
				DB::raw('IF((trips.reason) IS NULL,"--",trips.reason) as reason'),
				DB::raw('DATE_FORMAT(MAX(trips.created_at),"%d/%m/%Y %h:%i %p") as date')
			)
			->where('u.user_type_id', 3121)
			->where('e.company_id', Auth::user()->company_id)
			->where('trips.employee_id', Auth::user()->entity_id)
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
		;
		// if (!Entrust::can('view-all-trips')) {
		// 	$trips->where('trips.employee_id', Auth::user()->entity_id);
		// }

		//FILTERS
		if ($request->number) {
			$trips->where('trips.number', 'like', '%' . $request->number . '%');
		}
		if ($request->from_date) {
			$date = date('Y-m-d', strtotime($request->get('from_date')));
			$trips->where("trips.start_date", '>=', $date);
		}
		if ($request->to_date) {
			$date = date('Y-m-d', strtotime($request->get('to_date')));
			$trips->where("trips.end_date", '<=', $date);
		}
		// else {
		// 	$today = Carbon::today();
		// 	$from_date = $today->copy()->subMonths(3);
		// 	$trips->where('trips.start_date', '>=', $from_date);
		// }
		// if ($request->to_date) {
		// 	$trips->where('trips.end_date', '<=', $request->to_date);
		// } else {
		// 	$today = Carbon::today();
		// 	$to_date = $today->copy()->addMonths(3);
		// 	$trips->where('trips.end_date', '<=', $to_date);
		// }

		if ($request->status_ids) {
			// dump($request->status_ids);
			// dump($request->status_ids[0]);
			// $status_ids = explode(',', $request->status_ids[0]);
			// dd($status_ids);
			// dd($request->status_ids);
			$trips->whereIn('trips.status_id', json_decode($request->status_ids));
		} else {
			$trips->whereNotIn('trips.status_id', [3026]);
		}

		// if ($request->status_ids) {
		// 	$trips->where('trips.status_id', $request->status_ids);
		// } else {
		// 	$trips->where('trips.status_id', '!=', 3026);
		// }

		if ($request->purpose_ids) {
			$trips->where('trips.purpose_id', $request->purpose_ids);
		}

		if ($request->future_trip == '1') {
			$current_date = date('Y-m-d');
			$trips->where('trips.end_date', '<=', $current_date);
		}
		// if ($request->get('from_date')) {
		// 	$date = date('Y-m-d', strtotime($request->get('from_date')));
		// 	// dd($date);
		// 	$query->where("trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $request->get('from_date'));
		// }
		// if ($request->from_date && $request->to_date) {
		// 	$trips->where('trips.start_date', '>=', $request->from_date);
		// 	$trips->where('trips.end_date', '<=', $request->to_date);
		// } else {
		// 	$today = Carbon::today();
		// 	$from_date = $today->copy()->subMonths(3);
		// 	$to_date = $today->copy()->addMonths(3);
		// 	$trips->where('trips.start_date', '>=', $from_date);
		// 	$trips->where('trips.end_date', '<=', $to_date);
		// }

		// if ($request->status_ids && $request->status_ids[0]) {
		// 	$status_ids = explode(',', $request->status_ids[0]);
		// 	$trips->whereIn('trips.status_id', $status_ids);
		// } else {
		// 	$trips->whereNotIn('trips.status_id', [3026]);
		// }
		// if ($request->purpose_ids && $request->purpose_ids[0]) {
		// 	$purpose_ids = explode(',', $request->purpose_ids[0]);
		// 	$trips->whereIn('trips.purpose_id', $purpose_ids);
		// }
		// if ($request->from_city_id) {
		// 	$trips->whereIn('v.from_city_id', $request->from_city_id);
		// }
		// if ($request->to_city_id) {
		// 	$trips->whereIn('v.to_city_id', $request->to_city_id);
		// }
		return $trips;
	}

	public static function getEmployeeClaimList($request) {
		// dd(Auth::user()->company_id);
		$trips = Trip::from('trips')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->leftjoin('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->join('users as u', 'u.entity_id', 'e.id')
			->join('ey_employee_claims as claim', 'claim.trip_id', 'trips.id')

			->select(
				'trips.id',
				'trips.number',
				DB::raw('CONCAT(u.name," ( ",e.code," ) ") as ecode'),
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'trips.start_date',
				'trips.end_date',
				DB::raw('CONCAT(DATE_FORMAT(trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(trips.end_date,"%d-%m-%Y")) as travel_period'),
				// DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				DB::raw('FORMAT(claim.total_amount,2) as claim_amount'),
				//Changed to purpose_name. do not revert - Abdul
				'purpose.name as purpose_name',
				'trips.advance_received',
				'trips.status_id as status_id',
				'status.name as status_name',
				DB::raw('IF((trips.reason) IS NULL,"--",trips.reason) as reason'),
				DB::raw('DATE_FORMAT(MAX(trips.created_at),"%d/%m/%Y %h:%i %p") as date')
			)
			->where('u.user_type_id', 3121)
			->where('e.company_id', Auth::user()->company_id)
			->where('trips.employee_id', Auth::user()->entity_id)
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
		;
		// if (!Entrust::can('view-all-trips')) {
		// 	$trips->where('trips.employee_id', Auth::user()->entity_id);
		// }

		//FILTERS
		if ($request->number) {
			$trips->where('trips.number', 'like', '%' . $request->number . '%');
		}
		if ($request->from_date) {
			$date = date('Y-m-d', strtotime($request->get('from_date')));
			$trips->where("trips.start_date", '>=', $date);
		}
		if ($request->to_date) {
			$date = date('Y-m-d', strtotime($request->get('to_date')));
			$trips->where("trips.end_date", '<=', $date);
		}

		if ($request->status_ids) {
			// dump($request->status_ids);
			// dump($request->status_ids[0]);
			// $status_ids = explode(',', $request->status_ids[0]);
			// dd($status_ids);
			// dd($request->status_ids);
			$trips->whereIn('claim.status_id', json_decode($request->status_ids));
		} else {
			$trips->whereNotIn('claim.status_id', [3023]);
		}

		// if ($request->status_ids) {
		// 	$trips->where('trips.status_id', $request->status_ids);
		// } else {
		// 	$trips->where('trips.status_id', '!=', 3026);
		// }

		if ($request->purpose_ids) {
			$trips->where('trips.purpose_id', $request->purpose_ids);
		}

		// if ($request->get('from_date')) {
		// 	$date = date('Y-m-d', strtotime($request->get('from_date')));
		// 	// dd($date);
		// 	$query->where("trips.start_date", '>=', $date)->orWhere(DB::raw("-1"), $request->get('from_date'));
		// }
		// if ($request->from_date && $request->to_date) {
		// 	$trips->where('trips.start_date', '>=', $request->from_date);
		// 	$trips->where('trips.end_date', '<=', $request->to_date);
		// } else {
		// 	$today = Carbon::today();
		// 	$from_date = $today->copy()->subMonths(3);
		// 	$to_date = $today->copy()->addMonths(3);
		// 	$trips->where('trips.start_date', '>=', $from_date);
		// 	$trips->where('trips.end_date', '<=', $to_date);
		// }

		// if ($request->status_ids && $request->status_ids[0]) {
		// 	$status_ids = explode(',', $request->status_ids[0]);
		// 	$trips->whereIn('trips.status_id', $status_ids);
		// } else {
		// 	$trips->whereNotIn('trips.status_id', [3026]);
		// }
		// if ($request->purpose_ids && $request->purpose_ids[0]) {
		// 	$purpose_ids = explode(',', $request->purpose_ids[0]);
		// 	$trips->whereIn('trips.purpose_id', $purpose_ids);
		// }
		// if ($request->from_city_id) {
		// 	$trips->whereIn('v.from_city_id', $request->from_city_id);
		// }
		// if ($request->to_city_id) {
		// 	$trips->whereIn('v.to_city_id', $request->to_city_id);
		// }
		return $trips;
	}

	public static function getVerficationPendingList($r) {
		if (!empty($r->from_date)) {
			$from_date = date('Y-m-d', strtotime($r->from_date));
		} else {
			$from_date = null;
		}

		if (!empty($r->to_date)) {
			$to_date = date('Y-m-d', strtotime($r->to_date));
		} else {
			$to_date = null;
		}

		// dd($r->all());
		/*if(isset($r->period))
		{
		$date = explode(' to ', $r->period);
		$from_date = $date[0];
		$to_date = $date[1];
		dd($from_date,$to_date);
		$from_date = date('Y-m-d', strtotime($from_date));
		$to_date = date('Y-m-d', strtotime($to_date));
		 */
		//dd('d');
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
				'trips.start_date',
				'trips.end_date',
				'trips.status_id',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'purpose.name as purpose',
				DB::raw('IF((trips.advance_received) IS NULL,"--",FORMAT(trips.advance_received,2,"en_IN")) as advance_received'),
				// 'trips.created_at',
				DB::raw('DATE_FORMAT(MAX(trips.created_at),"%d/%m/%Y %h:%i %p") as date'),
				DB::raw('IF((trips.reason) IS NULL,"--",trips.reason) as reason'),
				'status.name as status', 'status.name as status_name'

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

			->where(function ($query) use ($from_date) {
				if (!empty($from_date)) {
					$query->where('trips.start_date', $from_date);
				}
			})
			->where(function ($query) use ($to_date) {
				if (!empty($to_date)) {
					$query->where('trips.end_date', $to_date);
				}
			})

		/*->where(function ($query) use ($r) {
			if ($r->get('period')) {
			$query->whereDate('v.date',">=",$from_date)->whereDate('v.date',"<=",$to_date);

			}
		*/
		;
		// if (!Entrust::can('verify-all-trips')) {
		$now = date('Y-m-d');
		$sub_employee_id = AlternateApprove::select('employee_id')
			->where('from', '<=', $now)
			->where('to', '>=', $now)
			->where('alternate_employee_id', Auth::user()->entity_id)
			->get()
			->toArray();
		//dd($sub_employee_id);
		$ids = array_column($sub_employee_id, 'employee_id');
		array_push($ids, Auth::user()->entity_id);
		if (count($sub_employee_id) > 0) {
			$trips->whereIn('trips.manager_id', $ids); //Alternate MANAGER
		} else {
			$trips->where('trips.manager_id', Auth::user()->entity_id); //MANAGER
		}

		//$trips->where('trips.manager_id', Auth::user()->entity_id);
		// }

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

	public static function deleteTrip($trip_id) {
		//CHECK IF FINANCIER APPROVE THE ADVANCE REQUEST
		$trip = Trip::where('id', $trip_id)->where('advance_request_approval_status_id', 3261)->first();
		if ($trip) {
			return response()->json(['success' => false, 'errors' => ['Trip cannot be deleted! Financier approved the advance amount']]);
		}

		//CHECK IF AGENT BOOKED TRIP VISITS
		$agent_visits_booked = Visit::where('trip_id', $trip_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
		if ($agent_visits_booked) {
			return response()->json(['success' => false, 'errors' => ['Trip cannot be deleted! Agent Booked visit! Request Agent for Cancelled Ticket']]);
		}
		//CHECK IF STATUS IS NEW OR MANAGER REJECTED OR MANAGER APPROVAL PENDING
		$status_exist = Trip::where('id', $trip_id)->whereIn('status_id', [3020, 3021, 3022, 3032])->first();
		if (!$status_exist) {
			return response()->json(['success' => false, 'errors' => ['Manager Approved so this trip cannot be deleted']]);
		}

		$status_exist = Trip::where('id', $trip_id)->where('advance_request_approval_status_id', 3261)->first();
		if ($status_exist) {
			return response()->json(['success' => false, 'errors' => ['Trip advance amount request approved so this trip cannot be deleted']]);
		}
		$trip = Trip::where('id', $trip_id)->first();
		$activity['entity_id'] = $trip->id;

		$agent_visits = Visit::where('trip_id', $trip_id)->where('booking_method_id', 3042)->whereIn('booking_status_id', [3061, 3062])->first();
		if ($agent_visits) {
			$trip = Trip::where('id', $trip_id)->update(['status_id' => 3032]);
		} else {
			$trip = Trip::where('id', $trip_id)->forceDelete();
		}
		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Deleted';
		$activity['activity'] = "delete";
		//dd($activity);

		$activity_log = ActivityLog::saveLog($activity);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		return response()->json(['success' => true]);

	}

	public static function cancelTrip($r) {
		$trip_id = $r->trip_id;
		//$trip = Trip::find($r->trip_id);
		//CHECK IF FINANCIER APPROVE THE ADVANCE REQUEST
		$trip = Trip::where('id', $trip_id)->where('advance_received', '>', 0)->where('status_id', 3028)->where('batch', 1)->first();
		if ($trip) {
			$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
			$notification = sendnotification($type = 12, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Trip Advance Amount Repay');
			//return response()->json(['success' => false, 'errors' => ['Trip cannot be Cancelled! Financier approved the advance amount']]);
		}

		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$agent_visits_booked = Visit::where('trip_id', $trip->id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
		if ($agent_visits_booked) {
			return response()->json(['success' => false, 'errors' => ['Trip Cannot be Cancelled! Agent Booked visit! Request Agent for Cancelled Ticket']]);
		}

		$trip->status_id = 3032;
		$trip->employee_remarks = $r->employee_remarks;
		$trip->save();

		$activity['entity_id'] = $trip_id;

		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Cancelled';
		$activity['activity'] = "cancel";
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);
		$visit = Visit::where('trip_id', $trip_id)->update(['status_id' => 3221]);
		$visit = Visit::where('trip_id', $trip_id)->where('booking_method_id', '=', 3040)->update(['booking_status_id' => 3062]);

		//TRIP CANCEL NOTIFICATION TO AGENT
		$trip_cancel_agent_notify_required = Config::where('id', 3984)->first()->name;
		$agentBookVisitIds = Visit::where('trip_id', $trip->id)
			->where('booking_method_id', 3042) //AGENT
			->pluck('id');
		// if (!empty($agentBookVisitIds)) {
		if (!empty($agentBookVisitIds) && $trip_cancel_agent_notify_required == "Yes") {
			sendEmailNotification($trip, $notification_type = 'Trip Cancel', $trip_type = "Outstation Trip", $agentBookVisitIds);
		}
		return response()->json(['success' => true]);
	}

	public static function deleteVisit($visit_id) {
		if ($visit_id) {
			$visit = Visit::where('id', $visit_id)->first();

			$agent_visits_booked = Visit::where('id', $visit_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
			if ($agent_visits_booked) {
				return response()->json(['success' => false, 'errors' => ['Visit Cannot be Deleted! Agent Booked this visit! Request Agent for Cancelled Ticket']]);
			}

			//Total Visit on this Trip
			$total_visits = Visit::where('trip_id', $visit->trip_id)->count();
			if ($total_visits > 1) {
				//Check Agent booking or not
				$activity['entity_id'] = $visit_id;
				$agent_visits = Visit::where('id', $visit_id)->where('booking_method_id', 3042)->whereIn('booking_status_id', [3061, 3062])->first();
				if ($agent_visits) {
					$visit->status_id = 3062; // Visit cancelled
					$visit->save();
				} else {
					$visit = $visit->forceDelete();
				}
				$activity['entity_type'] = 'visit';
				$activity['details'] = 'Visit is Deleted';
				$activity['activity'] = "delete";

				$activity_log = ActivityLog::saveLog($activity);
				return response()->json(['success' => true, 'message' => 'Visit Deleted successfully!']);
			} else {
				//CHECK IF FINANCIER APPROVE THE ADVANCE REQUEST
				$trip = Trip::where('id', $visit->trip_id)->where('advance_request_approval_status_id', 3261)->first();
				if ($trip) {
					return response()->json(['success' => false, 'errors' => ['Visit cannot be Deleted! Financier approved the advance amount']]);
				}

				$agent_visits = Visit::where('trip_id', $visit->trip_id)->where('booking_method_id', 3042)->whereIn('booking_status_id', [3061, 3062])->first();
				if ($agent_visits) {
					$trip = Trip::where('id', $visit->trip_id)->update(['status_id' => 3032]);
				} else {
					$trip = Trip::where('id', $visit->trip_id)->forceDelete();
				}
				return response()->json(['success' => true, 'message' => 'Trip Deleted successfully!']);
			}
		} else {
			return response()->json(['success' => false, 'errors' => ['Visit Cannot be Deleted']]);
		}
	}

	public static function cancelTripVisit($visit_id) {
		if ($visit_id) {
			$visit = Visit::where('id', $visit_id)->first();
			$agent_visits_booked = Visit::where('id', $visit_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
			if ($agent_visits_booked) {
				return response()->json(['success' => false, 'errors' => ['Visit Cannot be Deleted! Agent Booked this visit! Request Agent for Cancelled Ticket']]);
			}
			//Total Visit on this Trip
			$total_visits = Visit::where('trip_id', $visit->trip_id)->count();
			if ($total_visits > 1) {
				//Check Agent booking or not
				$activity['entity_id'] = $visit_id;
				$visit->booking_status_id = 3062; // Booking cancelled
				$visit->status_id = 3062; // Visit cancelled
				$visit->save();
				$activity['entity_type'] = 'visit';
				$activity['details'] = 'Visit is Cancelled';
				$activity['activity'] = "cancel";

				$activity_log = ActivityLog::saveLog($activity);

				//VISIT CANCEL NOTIFICATION TO AGENT
				$visit_cancel_agent_notify_required = Config::where('id', 3985)->first()->name;
				// if ($visit && $visit->booking_method_id == 3042) {
				if ($visit && $visit->booking_method_id == 3042 && $visit_cancel_agent_notify_required == 'Yes') {
					$tripData = Trip::find($visit->trip_id);
					sendEmailNotification($tripData, $notification_type = 'Visit Cancel', $trip_type = "Outstation Trip", [$visit->id]);
				}
				return response()->json(['success' => true, 'message' => 'Visit Cancelled successfully!']);
			} else {
				//CHECK IF FINANCIER APPROVE THE ADVANCE REQUEST
				$trip = Trip::where('id', $visit->trip_id)->where('advance_request_approval_status_id', 3261)->first();
				if ($trip) {
					return response()->json(['success' => false, 'errors' => ['Visit cannot be Cancelled! Financier approved the advance amount']]);
				}
				$trip = Trip::where('id', $visit->trip_id)->update(['status_id' => 3032]);
				return response()->json(['success' => true, 'message' => 'Trip Cancelled successfully!']);
			}
		} else {
			return response()->json(['success' => false, 'errors' => ['Visit Cannot be Deleted']]);
		}
	}

	public static function requestCancelVisitBooking($visit_id) {
		$visit = Visit::where('id', $visit_id)->update(['status_id' => 3221]);

		$trip = Trip::select('trips.employee_id', 'trips.id')->join('visits', 'visits.trip_id', 'trips.id')->where('visits.id', $visit_id)->get()->first();
		if (!$visit) {
			return response()->json(['success' => false, 'errors' => ['Booking Details not Found']]);
		}
		$employee = Employee::where('id', $trip->employee_id)->first();
		$user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 17, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Ticket Cancell');
		return response()->json(['success' => true]);
	}

	public static function cancelTripVisitBooking($visit_id) {
		if ($visit_id) {
			//CHECK IF AGENT BOOKED VISIT
			$agent_visits_booked = Visit::where('id', $visit_id)->where('booking_method_id', 3042)->where('booking_status_id', 3061)->first();
			if ($agent_visits_booked) {
				return response()->json(['success' => false, 'errors' => ['Visit cannot be deleted']]);
			}
			$visit = Visit::where('id', $visit_id)->first();
			$visit->booking_status_id = 3062; // Booking cancelled
			$visit->save();
			return response()->json(['success' => true]);
		} else {
			return response()->json(['success' => false, 'errors' => ['Bookings not cancelled']]);
		}
	}

	public static function approveTrip($r) {
		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$financier_approve = Auth::user()->company->financier_approve;
		$trip->advance_request_approval_status_id = Trip::select('id', 'advance_request_approval_status_id')
			->where('id', '=', $trip)->where('advance_request_approval_status_id', 3260)->get()->first();
		//dd($trip->advance_received);
		$trip->status_id = 3028;
		if ($financier_approve == '0') {
			if ($trip->advance_request_approval_status_id != null) {
				$trip->advance_request_approval_status_id = 3261; //Advance request Approved
			}
		}
		//PAYMENT SAVE
		/*$payment = Payment::firstOrNew(['entity_id' => $trip->id]);
		$payment->fill($r->all());
		$payment->payment_of_id = 3250;
		$payment->entity_id = $trip->id;
		$payment->created_by = Auth::user()->id;
		$payment->save();*/
		$trip->approve_remarks = $r->approve_remarks ? $r->approve_remarks : 0;
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Approved by Manager';
		$activity['activity'] = "approve";
		$activity_log = ActivityLog::saveLog($activity);
		$trip->visits()->update(['manager_verification_status_id' => 3081]);
		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		//Approval Log
		$approval_log = ApprovalLog::saveApprovalLog(3581, $trip->id, 3600, Auth::user()->entity_id, Carbon::now());
		$notification = sendnotification($type = 2, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Trip Approved');

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
		$activity['details'] = 'Trip is Rejected by Manager';
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);

		$trip->visits()->update(['manager_verification_status_id' => 3082]);

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type = 3, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Trip Rejected');

		return response()->json(['success' => true, 'message' => 'Trip rejected successfully!']);
	}

	public static function getClaimFormData($trip_id) {
		// if (!$trip_id) {
		//  $data['success'] = false;
		//  $data['message'] = 'Trip not found';
		//  $data['employee'] = [];
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
				'visits.selfBooking',
				'visits.attachments',
				'visits.agent',
				'visits.status',
				'visits.managerVerificationStatus',
				'cliam',
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
				'lodgings.lodgingTaxInvoice',
				'lodgings.drywashTaxInvoice',
				'lodgings.boardingTaxInvoice',
				'lodgings.othersTaxInvoice',
				'lodgings.discountTaxInvoice',
				'lodgings.roundoffTaxInvoice',
				'lodgings.stateType',
				'lodgings.city',
				'lodgings.attachments',
				'lodgings.shareDetails',
				'boardings',
				'boardings.stateType',
				'boardings.city',
				'boardings.attachments',
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
				// 'transport_attachments',
				// 'lodging_attachments',
				// 'boarding_attachments',
				// 'local_travel_attachments',
				'tripAttachments',
				'tripAttachments.attachmentName',
			])->find($trip_id);
		//dd($trip->lodgings);

		$ey_employee_data = EmployeeClaim::where('trip_id', $trip_id)->first();
		if (!empty($ey_employee_data) && (!Entrust::can('claim-edit') || (!in_array($trip->status_id, [3023, 3024, 3033])))) {
			$data['success'] = false;
			$data['error'] = 'Not possible to update the Claim details';
			return response()->json($data);
		}

		$data['attachment_type_lists'] = Trip::getAttachmentList($trip_id);
		$data['upload'] = URL::asset('public/img/content/file-icon.svg');
		$data['view'] = URL::asset('public/img/content/yatra/table/view.svg');
		$data['delete'] = URL::asset('public/img/content/yatra/table/delete.svg');
		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
		}
		if (count($trip->localTravels) > 0) {
			$data['action'] = 'Edit';
		} else {
			$data['action'] = 'Add';
		}
		$travelled_cities_with_dates = array();
		$lodge_cities = array();
		//GET TRAVEL DATE LIST FROM TRIP START AND END DATE
		$travel_dates_list = array();
		$date_range = Trip::getDatesFromRange($trip->start_date, $trip->end_date);
		if (!empty($date_range)) {
			$travel_dates_list[0]['id'] = '';
			$travel_dates_list[0]['name'] = 'Select Date';
			foreach ($date_range as $range_key => $range_val) {
				// $range_key++;
				// $travel_dates_list[$range_key]['id'] = $range_val;
				// $travel_dates_list[$range_key]['name'] = $range_val;
				// if (strtotime($range_val) <= strtotime(date('d-m-Y'))) {
					$range_key++;
					$travel_dates_list[$range_key]['id'] = $range_val;
					$travel_dates_list[$range_key]['name'] = $range_val;
				// }
			}
		}
		// Calculating Lodging days by Karthick T on 21-01-2022
		$visit_start_date = $visit_end_date = null;
		if (isset($trip->visits) && count($trip->visits) > 1) {
			foreach ($trip->visits as $visit_key => $visit) {
				if (!$visit_start_date) {
					$visit_start_date = date('Y-m-d', strtotime($visit->arrival_date));
				}
				$visit_end_date = date('Y-m-d', strtotime($visit->departure_date));
			}
		}
		$lodging_dates_list = $travel_dates_list;
		if ($visit_start_date && $visit_end_date) {
			$date_range = Trip::getDatesFromRange($visit_start_date, $visit_end_date);
			//dd($date_range);
			if (!empty($date_range)) {
				$lodging_dates_list = [];
				$lodging_dates_list[0]['id'] = '';
				$lodging_dates_list[0]['name'] = 'Select Date';
				foreach ($date_range as $range_key => $range_val) {
					$range_key++;
					$lodging_dates_list[$range_key]['id'] = $range_val;
					$lodging_dates_list[$range_key]['name'] = $range_val;
				}
			}
		}
		// Calculating Lodging days by Karthick T on 21-01-2022

		$data['travelled_cities_with_dates'] = $travelled_cities_with_dates;
		$data['lodge_cities'] = $lodge_cities;
		$data['travel_dates_list'] = $travel_dates_list;
		$data['lodging_dates_list'] = $travel_dates_list;

		$to_cities = Visit::where('trip_id', $trip_id)->pluck('to_city_id')->toArray();
		$data['success'] = true;
		$data['employee'] = $employee = Employee::select('users.name as name', 'employees.code as code', 'designations.name as designation', 'entities.name as grade', 'employees.grade_id', 'employees.id', 'employees.gender', 'gae.two_wheeler_per_km', 'gae.four_wheeler_per_km', 'gae.outstation_trip_amount', 'sbus.id as sbu_id', 'sbus.name as sbu_name','gae.check_guest_house_approval_attachment','gae.is_leader_grade')
			->leftjoin('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
			->leftjoin('designations', 'designations.id', 'employees.designation_id')
			->leftjoin('users', 'users.entity_id', 'employees.id')
			->leftjoin('entities', 'entities.id', 'employees.grade_id')
			->leftJoin('sbus', 'sbus.id', 'employees.sbu_id')
			->where('employees.id', $trip->employee_id)
			->where('users.user_type_id', 3121)->first();
		$travel_cities = Visit::leftjoin('ncities as cities', 'visits.to_city_id', 'cities.id')
			->where('visits.trip_id', $trip->id)->pluck('cities.name')->toArray();
		$data['travel_cities'] = !empty($travel_cities) ? trim(implode(', ', $travel_cities)) : '--';
		// $start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.departure_date),"%d/%m/%Y") as start_date'))->first();
		// $end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MAX(visits.departure_date),"%d/%m/%Y") as end_date'))->first();
		// $days = $trip->visits()->select(DB::raw('DATEDIFF(MAX(visits.departure_date),MIN(visits.departure_date))+1 as days'))->first();

		//Get Own Vehicle details
		$vehicle_details = Entity::join('travel_mode_category_type', 'travel_mode_category_type.travel_mode_id', 'entities.id')->where('travel_mode_category_type.category_id', 3400)->where('entities.company_id', Auth::user()->company_id)->where('entities.entity_type_id', 502)->select('entities.name', 'entities.id')->get();
		$values = [];
		foreach ($vehicle_details as $key => $value) {
			$stripped = strtolower(preg_replace('/\s/', '', $value->name));
			if ($stripped == 'twowheeler') {
				$values[$value->id] = $employee->two_wheeler_per_km;
			} elseif ($stripped == 'fourwheeler') {
				$values[$value->id] = $employee->four_wheeler_per_km;
			} else {
				$values[$value->id] = '0';
			}
		}
		// dd($values);

		$data['travel_values'] = $values;
		// dd($values);

		//DAYS CALC BTW START & END DATE
		$datediff = strtotime($trip->end_date) - strtotime($trip->start_date);
		$no_of_days = ($datediff / (60 * 60 * 24)) + 1;
		$trip->days = $no_of_days ? $no_of_days : 0;

		//DONT REVERT - ABDUL
		$trip->cities = $data['cities'] = count($travel_cities) > 0 ? trim(implode(', ', $travel_cities)) : '--';
		$data['travel_dates'] = $travel_dates = Visit::select(DB::raw('MAX(DATE_FORMAT(visits.arrival_date,"%d/%m/%Y")) as max_date'), DB::raw('MIN(DATE_FORMAT(visits.departure_date,"%d/%m/%Y")) as min_date'))->where('visits.trip_id', $trip->id)->first();
		// }
		if (!empty($to_cities)) {
			$city_list = collect(NCity::select('id', 'name', 'guest_house_status')->where('company_id', Auth::user()->company_id)->whereIn('id', $to_cities)->groupby('id')->get()->prepend(['id' => '', 'name' => 'Select City']));
		} else {
			$city_list = [];
		}

		$cities_with_expenses = NCity::select('id', 'name')->where('company_id', Auth::user()->company_id)->whereIn('id', $to_cities)->groupby('id')->get()->keyBy('id')->toArray();
		foreach ($cities_with_expenses as $key => $cities_with_expenses_value) {
			$city_category_id = NCity::where('id', $key)->where('company_id', Auth::user()->company_id)->first();
			if ($city_category_id) {
				$transport_expense_type = DB::table('grade_expense_type')->where('grade_id', $trip->employee->grade_id)->where('expense_type_id', 3000)->where('city_category_id', $city_category_id->category_id)->first();
				if (!$transport_expense_type) {
					$cities_with_expenses[$key]['transport']['id'] = 3000;
					$cities_with_expenses[$key]['transport']['grade_id'] = $trip->employee->grade_id;
					$cities_with_expenses[$key]['transport']['eligible_amount'] = '0.00';
				} else {
					$cities_with_expenses[$key]['transport']['id'] = 3000;
					$cities_with_expenses[$key]['transport']['grade_id'] = $trip->employee->grade_id;
					$cities_with_expenses[$key]['transport']['eligible_amount'] = $transport_expense_type->eligible_amount;
				}
				$lodge_expense_type = DB::table('grade_expense_type')->where('grade_id', $trip->employee->grade_id)->where('expense_type_id', 3001)->where('city_category_id', $city_category_id->category_id)->first();
				if (!$lodge_expense_type) {
					$cities_with_expenses[$key]['lodge']['id'] = 3001;
					$cities_with_expenses[$key]['lodge']['grade_id'] = $trip->employee->grade_id;
					$cities_with_expenses[$key]['lodge']['home']['eligible_amount'] = '0.00';
					$cities_with_expenses[$key]['lodge']['home']['perc'] = 0;
					$cities_with_expenses[$key]['lodge']['normal']['eligible_amount'] = '0.00';
				} else {

					//STAY TYPE HOME
					//GET GRADE STAY TYPE
					$grade_stay_type = DB::table('grade_advanced_eligibility')->where('grade_id', $trip->employee->grade_id)->first();
					if ($grade_stay_type) {
						if ($grade_stay_type->stay_type_disc) {
							$percentage = (int) $grade_stay_type->stay_type_disc;
							$totalWidth = $lodge_expense_type->eligible_amount;
							$home_eligible_amount = ($percentage / 100) * $totalWidth;
						} else {
							$percentage = 0;
							$home_eligible_amount = $lodge_expense_type->eligible_amount;
						}
					} else {
						$percentage = 0;
						$home_eligible_amount = $lodge_expense_type->eligible_amount;
					}
					$cities_with_expenses[$key]['lodge']['id'] = 3001;
					$cities_with_expenses[$key]['lodge']['grade_id'] = $trip->employee->grade_id;
					$cities_with_expenses[$key]['lodge']['home']['eligible_amount'] = $home_eligible_amount;
					$cities_with_expenses[$key]['lodge']['home']['perc'] = $percentage;
					$cities_with_expenses[$key]['lodge']['normal']['eligible_amount'] = $lodge_expense_type->eligible_amount;
				}
				$board_expense_type = DB::table('grade_expense_type')
					->where('grade_id', $trip->employee->grade_id)
					->where('expense_type_id', 3002)
					->where('city_category_id', $city_category_id->category_id)
					->first();
				if (!$board_expense_type) {
					$cities_with_expenses[$key]['board']['id'] = 3002;
					$cities_with_expenses[$key]['board']['grade_id'] = $trip->employee->grade_id;
					$cities_with_expenses[$key]['board']['eligible_amount'] = '0.00';
					$cities_with_expenses[$key]['board']['less_than_240_ea'] = '0.00';
					$cities_with_expenses[$key]['board']['less_than_480_ea'] = '0.00';
					$cities_with_expenses[$key]['board']['less_than_1440_ea'] = '0.00';
				} else {
					$cities_with_expenses[$key]['board']['id'] = 3002;
					$cities_with_expenses[$key]['board']['grade_id'] = $trip->employee->grade_id;
					$cities_with_expenses[$key]['board']['eligible_amount'] = $board_expense_type->eligible_amount;
					$cities_with_expenses[$key]['board']['less_than_240_ea'] = $board_expense_type->less_than_240;
					$cities_with_expenses[$key]['board']['less_than_480_ea'] = $board_expense_type->less_than_480;
					$cities_with_expenses[$key]['board']['less_than_1440_ea'] = $board_expense_type->less_than_1440;
				}
			} else {
				$cities_with_expenses[$key]['transport'] = [];
				$cities_with_expenses[$key]['lodge'] = [];
				$cities_with_expenses[$key]['board'] = [];
			}

		}
		$data['cities_with_expenses'] = $cities_with_expenses;
		$travel_cities_list = collect(Visit::leftjoin('ncities as cities', 'visits.to_city_id', 'cities.id')
				->where('visits.trip_id', $trip->id)
				->select('cities.id', 'cities.name')
				->orderBy('visits.id', 'asc')
				->get()->prepend(['id' => '', 'name' => 'Select City']));
		$booking_type_list = collect(Config::getBookingTypeTypeList()->prepend(['id' => '', 'name' => 'Select Booked By']));
		$purpose_list = collect(Entity::uiPurposeList()->prepend(['id' => '', 'name' => 'Select Purpose']));
		$travel_mode_list = collect(Entity::uiClaimTravelModeList()->prepend(['id' => '', 'name' => 'Select Travel Mode']));
		$local_travels = Entity::uiClaimLocaTravelModeList();
		$expense_types = Entity::uiClaimExpenseList();
		$local_travels_expense_types = $local_travels->merge($expense_types);

		// $local_travel_mode_list = collect(Entity::uiClaimLocaTravelModeList()->prepend(['id' => '', 'name' => 'Select Local Travel Mode']));
		$local_travel_mode_list = collect($local_travels_expense_types->prepend(['id' => '', 'name' => 'Select Local Travel / Expense Type']));
		$showOfficeGuestHouse = true;
		if (!empty($trip->employee->grade) && in_array($trip->employee->grade->name, ['W00', 'W01', 'W02', 'W03', 'W04', 'W05', 'W06', 'W07', 'S00', 'S01', 'S02', 'S03', 'S04', 'S05', 'S06', 'S07'])) {
			$showOfficeGuestHouse = false;
		}
		// $stay_type_list = collect(Config::getLodgeStayTypeList()->prepend(['id' => '', 'name' => 'Select Stay Type']));
		$stay_type_list = collect(
			Config::where('config_type_id', 521)
				->select('id', 'name')
				->where(function ($q) use ($showOfficeGuestHouse) {
					if (!$showOfficeGuestHouse) {
						$q->whereNotIn('id', [3342]);
					}

				})
				->get()
				->prepend(['id' => '', 'name' => 'Select Stay Type']));
		$boarding_type_list = collect(Config::getBoardingTypeList()->prepend(['id' => '', 'name' => 'Select Type']));
		$sharing_type_list = collect(Config::whereIn('id', [3811, 3812])->select('id', 'name')->get());
		$data['extras'] = [
			'purpose_list' => $purpose_list,
			'travel_mode_list' => $travel_mode_list,
			'local_travel_mode_list' => $local_travel_mode_list,
			'city_list' => $city_list,
			'stay_type_list' => $stay_type_list,
			'boarding_type_list' => $boarding_type_list,
			'booking_type_list' => $booking_type_list,
			'travel_cities_list' => $travel_cities_list,
			'sharing_type_list' => $sharing_type_list,
		];
		$state_code = NState::leftJoin('ncities', 'ncities.state_id', 'nstates.id')
			->leftJoin('ey_addresses', 'ey_addresses.city_id', 'ncities.id')
			->where('ey_addresses.address_of_id', 3160)
			->where('ey_addresses.entity_id', $trip->outlet_id)
			->pluck('nstates.gstin_state_code')->first();
		$user_company_id = Auth::user()->company_id;
		$gstin_enable = Company::where('id', $user_company_id)->pluck('gstin_enable')->first();
		$grade_travel = Entity::select('entities.id', 'entities.name')
			->join('grade_travel_mode', 'grade_travel_mode.travel_mode_id', 'entities.id')
			->join('employees', 'employees.grade_id', 'grade_travel_mode.grade_id')
			->where('entities.entity_type_id', 502)
			->where('employees.id', Auth::user()->entity_id)
			->where('entities.company_id', Auth::user()->company_id)
			->get();
		$km_end_twowheeler = VisitBooking::latest('id')->where('travel_mode_id', '=', 15)->pluck('km_end')->first();
		$km_end_fourwheeler = VisitBooking::latest('id')->where('travel_mode_id', '=', 16)->pluck('km_end')->first();
		$data['gstin_enable'] = $gstin_enable;

		//LODGE SHARE DETAILS
		if (count($trip->lodgings) > 0) {
			foreach ($trip->lodgings as $lodge_data) {
				$lodge_share_data = [];
				foreach ($lodge_data->shareDetails as $share_key => $share_data) {
					$lodge_share_data[$share_key] = LodgingShareDetail::select([
						'lodging_share_details.id',
						'employees.id as employee_id',
						'employees.code as employee_code',
						'employees.grade_id',
						'outlets.code as outlet_code',
						'outlets.name as outlet_name',
						'users.name as user_name',
						'grades.name as grade',
						'designations.name as designation',
						'sbus.name as sbu',
					])
						->join('employees', 'employees.id', 'lodging_share_details.employee_id')
						->join('outlets', 'outlets.id', 'employees.outlet_id')
						->join('entities as grades', 'grades.id', 'employees.grade_id')
						->leftjoin('designations', 'designations.id', 'employees.designation_id')
						->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
						->join('users', 'users.entity_id', 'employees.id')
						->where('users.user_type_id', 3121) //EMPLOYEE
						->where('lodging_share_details.id', $share_data->id)
						->first();

					$lodge_share_data[$share_key]->eligible_amount = 0.00;

					$lodge_city_category_id = NCity::where('id', $lodge_data->city_id)
						->pluck('category_id')
						->first();
					// $lodge_share_data[$share_key]['normal'] = [
					// 	'eligible_amount' => 0,
					// ];

					if ($lodge_city_category_id) {
						$lodge_expense_config = DB::table('grade_expense_type')
							->where('grade_id', $lodge_share_data[$share_key]->grade_id)
							->where('expense_type_id', 3001) //LODGING EXPENSES
							->where('city_category_id', $lodge_city_category_id)
							->first();
						if (!empty($lodge_expense_config)) {
							// $lodge_share_data[$share_key]['normal'] = [
							// 	'eligible_amount' => $lodge_expense_config->eligible_amount,
							// ];
							$lodge_share_data[$share_key]->eligible_amount = $lodge_expense_config->eligible_amount;
						}
					}
				}
				$lodge_data['sharing_employees'] = $lodge_share_data;
			}
		}

		$data['trip'] = $trip;
		$data['km_end_twowheeler'] = $km_end_twowheeler;
		$data['km_end_fourwheeler'] = $km_end_fourwheeler;
		$data['grade_travel'] = $grade_travel;
		$data['state_code'] = $state_code;
		$data['sbu_lists'] = Sbu::getSbuList();
		$data['operating_states'] = OperatingStates::join('nstates', 'nstates.id', 'operating_states.nstate_id')
			->where('operating_states.company_id', Auth::user()->company_id)
			->pluck('nstates.gstin_state_code');
		$data['employee_return_payment_mode_list'] = collect(Config::select('name', 'id')->where('config_type_id', 569)->orderBy('id', 'asc')->get());
		$data['employee_return_payment_balance_cash_limit'] = Config::where('id', 4037)->first()->name;
		$data['company_data'] = Company::select('id','transfer_bank_name','transfer_account_number','transfer_ifsc_code')->where('id', Auth::user()->company_id)->first();
		return response()->json($data);
	}

	public static function getFilterData($type = NULL) {
		$data = [];
		$data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)
				->orderBy('users.name')
				->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);

		if ($type == 1) {
			$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 501)->where(DB::raw('LOWER(name)'), '!=', strtolower("New"))->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		} elseif ($type == 2) {
			$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 535)->where(DB::raw('LOWER(name)'), '!=', strtolower("resolved"))->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		} else {
			$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 501)->where(DB::raw('LOWER(name)'), '!=', strtolower("New"))->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		}

		$data['outlet_list'] = collect(Outlet::select('name', 'id')->get())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$data['all_employee_list'] = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.company_id', Auth::user()->company_id)
				->orderBy('users.name')
				->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);

		$data['financier_status_list'] = collect(Config::select('name', 'id')->whereIn('id', [3034, 3030, 3026, 3025, 3031])->orderBy('id', 'asc')->get())->prepend(['id' => '', 'name' => 'Select Status']);
		$data['employee_return_payment_mode_list'] = collect(Config::select('name', 'id')->where('config_type_id', 569)->whereNotIn('id',[4012])->orderBy('id', 'asc')->get());
		$data['employee_return_payment_bank_list'] = collect(Config::select('name', 'id')->where('config_type_id', 570)->orderBy('id', 'asc')->get());
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

		$trip = Trip::with([
			'visits' => function ($q) {
				$q->orderBy('id', 'asc');
			},
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.selfBooking',
			'visits.attachments',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'advanceRequestStatus',
			'employee',
			'employee.user',
			'employee.tripEmployeeClaim' => function ($q) use ($trip_id) {
				$q->where('trip_id', $trip_id);
			},
			'employee.grade',
			'employee.designation',
			'employee.reportingTo',
			'employee.reportingTo.user',
			'employee.outlet',
			'employee.Sbu',
			'employee.Sbu.lob',
			'selfVisits' => function ($q) {
				$q->orderBy('id', 'asc');
			},
			'purpose',
			'lodgings',
			'lodgings.lodgingTaxInvoice',
			'lodgings.drywashTaxInvoice',
			'lodgings.boardingTaxInvoice',
			'lodgings.othersTaxInvoice',
			'lodgings.discountTaxInvoice',
			'lodgings.roundoffTaxInvoice',
			'lodgings.city',
			'lodgings.stateType',
			'lodgings.attachments',
			'lodgings.sharingType',
			'lodgings.shareDetails',
			'lodgings.shareDetails.employee',
			'lodgings.shareDetails.employee.user',
			'lodgings.shareDetails.employee.outlet',
			'lodgings.shareDetails.employee.grade',
			'lodgings.shareDetails.employee.designation',
			'lodgings.shareDetails.employee.Sbu',
			'boardings',
			'boardings.stateType',
			'boardings.city',
			'boardings.attachments',
			'localTravels',
			'localTravels.fromCity',
			'localTravels.toCity',
			'localTravels.travelMode',
			'selfVisits.fromCity',
			'selfVisits.toCity',
			'selfVisits.travelMode',
			'selfVisits.bookingMethod',
			'selfVisits.selfBooking',
			'selfVisits.agent',
			'selfVisits.status',
			'selfVisits.attachments',
			// 'transport_attachments',
			// 'lodging_attachments',
			// 'boarding_attachments',
			'google_attachments',
			// 'local_travel_attachments',
			'cliam.sbu',
			'cliam.sbu.lob',
			'cliam.employeeReturnPaymentMode',
			'cliam.employeeReturnPaymentBank',
			'tripAttachments',
			'tripAttachments.attachmentName',

		])->find($trip_id);
		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
		}

		// Trip employee claim amount update by Karthick T on 22-08-2022
		/*if (isset($trip->employee->tripEmployeeClaim) && $trip->employee->tripEmployeeClaim) {
			$tax_details = EmployeeClaim::select(
					DB::raw('SUM(IFNULL(lodgings.cgst, 0) + IFNULL(lodgings.sgst, 0) + IFNULL(lodgings.igst, 0)) as lodging_tax'),
					DB::raw('SUM(IFNULL(boardings.cgst, 0) + IFNULL(boardings.sgst, 0) + IFNULL(boardings.igst, 0)) as boarding_tax'),
					DB::raw('SUM(IFNULL(local_travels.cgst, 0) + IFNULL(local_travels.sgst, 0) + IFNULL(local_travels.igst, 0)) as local_travel_tax')
				)->leftJoin('lodgings', 'lodgings.trip_id', 'ey_employee_claims.trip_id')
				->leftJoin('boardings', 'boardings.trip_id', 'ey_employee_claims.trip_id')
				->leftJoin('local_travels', 'local_travels.trip_id', 'ey_employee_claims.trip_id')
				->where('ey_employee_claims.trip_id', $trip->id)
				->where('ey_employee_claims.employee_id', $trip->employee->id)
				->get()
				->toArray();
			if (count($tax_details) > 0) {
				$lodging_tax = array_sum(array_column($tax_details, 'lodging_tax'));
				$boarding_tax = array_sum(array_column($tax_details, 'boarding_tax'));
				$local_travel_tax = array_sum(array_column($tax_details, 'local_travel_tax'));

				$local_travel_tax = number_format(array_sum(array_column($tax_details, 'local_travel_tax')), 2, '.', '');

				$lodgingTotal = $trip->employee->tripEmployeeClaim->lodging_total + $lodging_tax;
				$boardingTotal = $trip->employee->tripEmployeeClaim->boarding_total + $boarding_tax;
				$localTravelTotal = $trip->employee->tripEmployeeClaim->local_travel_total + $local_travel_tax;

				$trip->employee->tripEmployeeClaim->lodging_total = number_format($lodgingTotal, 2, '.', ',');
				$trip->employee->tripEmployeeClaim->boarding_total = number_format($boardingTotal, 2, '.', ',');
				$trip->employee->tripEmployeeClaim->local_travel_total = number_format($localTravelTotal, 2, '.', ',');
				$total_amount = +$trip->employee->tripEmployeeClaim->transport_total
							+ +$lodgingTotal
							+ +$boardingTotal
							+ +$localTravelTotal;

				$trip->employee->tripEmployeeClaim->total_amount = number_format($total_amount, 2, '.', '');
			}
		}*/
		// Trip employee claim amount update by Karthick T on 22-08-2022

		$travel_cities = Visit::leftjoin('ncities as cities', 'visits.to_city_id', 'cities.id')
			->where('visits.trip_id', $trip->id)->pluck('cities.name')->toArray();

		// $transport_total = Visit::select(
		// 	DB::raw('COALESCE(SUM(visit_bookings.amount), 0.00) as visit_amount'),
		// 	DB::raw('COALESCE(SUM(visit_bookings.tax), 0.00) as visit_tax'),
		// 	DB::raw('COALESCE(SUM(visit_bookings.toll_fee), 0.00) as toll_fees')
		// )
		// 	->leftjoin('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
		// 	->where('visits.trip_id', $trip_id)
		// 	->where('visits.booking_method_id', 3040)
		// 	->groupby('visits.id')
		// 	->get()
		// 	->toArray();
		// $visit_amounts = array_column($transport_total, 'visit_amount');
		// $visit_taxes = array_column($transport_total, 'visit_tax');
		// $visit_toll_fees = array_column($transport_total, 'toll_fees');
		// $visit_amounts_total = array_sum($visit_amounts);
		// $visit_taxes_total = array_sum($visit_taxes);

		// $transport_total_amount = $visit_amounts_total ? $visit_amounts_total : 0.00;
		// $transport_total_tax = $visit_taxes_total ? $visit_taxes_total : 0.00;
		// $data['transport_total_amount'] = number_format($transport_total_amount, 2, '.', '');

		// $lodging_total = Lodging::select(
		// 	DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
		// 	DB::raw('COALESCE(SUM(tax), 0.00) as tax')
		// )
		// 	->where('trip_id', $trip_id)
		// 	->groupby('trip_id')
		// 	->first();
		// $lodging_total_amount = $lodging_total ? $lodging_total->amount : 0.00;
		// $lodging_total_tax = $lodging_total ? $lodging_total->tax : 0.00;
		// $data['lodging_total_amount'] = number_format($lodging_total_amount, 2, '.', '');

		// $boardings_total = Boarding::select(
		// 	DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
		// 	DB::raw('COALESCE(SUM(tax), 0.00) as tax')
		// )
		// 	->where('trip_id', $trip_id)
		// 	->groupby('trip_id')
		// 	->first();
		// $boardings_total_amount = $boardings_total ? $boardings_total->amount : 0.00;
		// $boardings_total_tax = $boardings_total ? $boardings_total->tax : 0.00;
		// $data['boardings_total_amount'] = number_format($boardings_total_amount, 2, '.', '');

		// $local_travels_total = LocalTravel::select(
		// 	DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
		// 	DB::raw('COALESCE(SUM(tax), 0.00) as tax')
		// )
		// 	->where('trip_id', $trip_id)
		// 	->groupby('trip_id')
		// 	->first();
		// $local_travels_total_amount = $local_travels_total ? $local_travels_total->amount : 0.00;
		// $local_travels_total_tax = $local_travels_total ? $local_travels_total->tax : 0.00;
		// $data['local_travels_total_amount'] = number_format($local_travels_total_amount, 2, '.', '');

		// $total_amount = $transport_total_amount + $transport_total_tax + $lodging_total_amount + $lodging_total_tax + $boardings_total_amount + $boardings_total_tax + $local_travels_total_amount + $local_travels_total_tax;
		// $data['total_amount'] = number_format($total_amount, 2, '.', '');

		$data['travel_cities'] = !empty($travel_cities) ? trim(implode(', ', $travel_cities)) : '--';
		$data['travel_dates'] = $travel_dates = Visit::select(DB::raw('MAX(DATE_FORMAT(visits.arrival_date,"%d/%m/%Y")) as max_date'), DB::raw('MIN(DATE_FORMAT(visits.departure_date,"%d/%m/%Y")) as min_date'))->where('visits.trip_id', $trip->id)->first();

		$data['trip_claim_rejection_list'] = collect(Entity::trip_claim_rejection()->prepend(['id' => '', 'name' => 'Select Rejection Reason']));

		$data['success'] = true;

		$lodging_bal_amount = $trip->lodgings->sum('amount');
		$lodging_tax_amount = $trip->lodgings->sum('tax');
		$lodging_bal_amount = number_format($lodging_bal_amount, 2, '.', '');
		$lodging_tax_amount = number_format($lodging_tax_amount, 2, '.', '');
		$trip->lodging_bal_amount = $lodging_bal_amount;
		$trip->lodging_tax_amount = $lodging_tax_amount;

		$current_year_arr = calculateFinancialYearForDate(date('m'));
		$to_date = $current_year_arr['to_fy'];
		$from_date = $current_year_arr['from_fy'];
		$emp_amount_financial_year_from = date('Y', strtotime($from_date));
		$emp_amount_financial_year_to = date('Y', strtotime($to_date));
		$emp_fy_amounts = EmployeeClaim::select(
			'ey_employee_claims.total_amount',
			'ey_employee_claims.transport_total',
			'ey_employee_claims.lodging_total',
			'ey_employee_claims.boarding_total',
			'ey_employee_claims.local_travel_total',
			'ey_employee_claims.beta_amount'
		)->join('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->whereDate('trips.claimed_date', '>=', $from_date)
			->whereDate('trips.claimed_date', '<=', $to_date)
			->where('ey_employee_claims.status_id', 3026)
			->where('ey_employee_claims.employee_id', $trip->employee->id)
			->get()
			->toArray();
		$emp_claim_amount = $transport_amount = $lodging_amount = $boarding_amount = $local_travel_amount = $beta_amount = 0;
		$emp_trip_count = count($emp_fy_amounts);
		if (count($emp_fy_amounts) > 0) {
			$emp_claim_amount = number_format(array_sum(array_column($emp_fy_amounts, 'total_amount')), 2, '.', ',');
			$transport_amount = number_format(array_sum(array_column($emp_fy_amounts, 'transport_total')), 2, '.', ',');
			$lodging_amount = number_format(array_sum(array_column($emp_fy_amounts, 'lodging_total')), 2, '.', ',');
			$boarding_amount = number_format(array_sum(array_column($emp_fy_amounts, 'boarding_total')), 2, '.', ',');
			$local_travel_amount = number_format(array_sum(array_column($emp_fy_amounts, 'local_travel_total')), 2, '.', ',');
			$beta_amount = number_format(array_sum(array_column($emp_fy_amounts, 'beta_amount')), 2, '.', ',');
		}
		$visit = Visit::select('id')->where('trip_id', $trip->id)->where('booking_method_id', 3040)->get()->toArray();
		$lodge = Lodging::where('trip_id', $trip->id)->pluck('trip_id')->count();
		$board = Boarding::where('trip_id', $trip->id)->pluck('trip_id')->count();
		$other = LocalTravel::where('trip_id', $trip->id)->pluck('trip_id')->count();
		$tax_details = Trip::select('lodgings.amount as lodging_basic', 'lodgings.cgst as lodging_cgst', 'lodgings.sgst as lodging_sgst', 'lodgings.igst as lodging_igst', 'lodgings.round_off as lodging_round_off', 'boardings.amount as boarding_basic', 'boardings.cgst as boarding_cgst', 'boardings.sgst as boarding_sgst', 'boardings.igst as boarding_igst', 'local_travels.amount as local_basic', 'local_travels.cgst as local_cgst', 'local_travels.sgst as local_sgst', 'local_travels.igst as local_igst')
			->leftjoin('lodgings', 'lodgings.trip_id', 'trips.id')
			->leftjoin('boardings', 'boardings.trip_id', 'trips.id')
			->leftjoin('local_travels', 'local_travels.trip_id', 'trips.id')
			->where('trips.id', $trip->id)
		//->where('trips.employee_id', $trip->employee->id)
			->get()->toArray();
		//dd($tax_details);
		$transport_tax_details = Trip::select('visit_bookings.cgst as transport_cgst', 'visit_bookings.sgst as transport_sgst', 'visit_bookings.amount as transport_basic', 'visit_bookings.igst as transport_igst')
			->join('visits', 'visits.trip_id', 'trips.id')
			->join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
			->where('visits.booking_method_id', 3040)
			->where('trips.id', $trip->id)
		//->where('trips.employee_id', $trip->employee->id)
			->get()->toArray();

		if (count($visit) > 1) {
			$transport_basic = number_format(array_sum(array_column($transport_tax_details, 'transport_basic')), 2, '.', ',');
			$transport_cgst = number_format(array_sum(array_column($transport_tax_details, 'transport_cgst')), 2, '.', ',');
			$transport_sgst = number_format(array_sum(array_column($transport_tax_details, 'transport_sgst')), 2, '.', ',');
			$transport_igst = number_format(array_sum(array_column($transport_tax_details, 'transport_igst')), 2, '.', ',');
		} else {
			$transport_basic = isset($transport_tax_details[0]['transport_basic']) ? $transport_tax_details[0]['transport_basic'] : 0;
			$transport_cgst = isset($transport_tax_details[0]['transport_cgst']) ? $transport_tax_details[0]['transport_cgst'] : 0;
			$transport_sgst = isset($transport_tax_details[0]['transport_sgst']) ? $transport_tax_details[0]['transport_sgst'] : 0;
			$transport_igst = isset($transport_tax_details[0]['transport_igst']) ? $transport_tax_details[0]['transport_igst'] : 0;
		}
		if ($lodge > 1) {
			$lodging_basic = number_format(array_sum(array_column($tax_details, 'lodging_basic')), 2, '.', ',');
			$lodging_cgst = number_format(array_sum(array_column($tax_details, 'lodging_cgst')), 2, '.', ',');
			$lodging_sgst = number_format(array_sum(array_column($tax_details, 'lodging_sgst')), 2, '.', ',');
			$lodging_igst = number_format(array_sum(array_column($tax_details, 'lodging_igst')), 2, '.', ',');
			$lodging_round_off = number_format(array_sum(array_column($tax_details, 'lodging_round_off')), 2, '.', ',');
		} else {
			$lodging_basic = isset($tax_details[0]['lodging_basic']) ? $tax_details[0]['lodging_basic'] : 0;
			$lodging_cgst = isset($tax_details[0]['lodging_cgst']) ? $tax_details[0]['lodging_cgst'] : 0;
			$lodging_sgst = isset($tax_details[0]['lodging_sgst']) ? $tax_details[0]['lodging_sgst'] : 0;
			$lodging_igst = isset($tax_details[0]['lodging_igst']) ? $tax_details[0]['lodging_igst'] : 0;
			$lodging_round_off = isset($tax_details[0]['lodging_round_off']) ? $tax_details[0]['lodging_round_off'] : 0;
		}
		if ($board > 1) {
			$boarding_basic = number_format(array_sum(array_column($tax_details, 'boarding_basic')), 2, '.', ',');
			$boarding_cgst = number_format(array_sum(array_column($tax_details, 'boarding_cgst')), 2, '.', ',');
			$boarding_sgst = number_format(array_sum(array_column($tax_details, 'boarding_sgst')), 2, '.', ',');
			$boarding_igst = number_format(array_sum(array_column($tax_details, 'boarding_igst')), 2, '.', ',');
		} else {
			$boarding_basic = isset($tax_details[0]['boarding_basic']) ? $tax_details[0]['boarding_basic'] : 0;
			$boarding_cgst = isset($tax_details[0]['boarding_cgst']) ? $tax_details[0]['boarding_cgst'] : 0;
			$boarding_sgst = isset($tax_details[0]['boarding_sgst']) ? $tax_details[0]['boarding_sgst'] : 0;
			$boarding_igst = isset($tax_details[0]['boarding_igst']) ? $tax_details[0]['boarding_igst'] : 0;

		}
		if ($other > 1) {
			$local_travel_basic = number_format(array_sum(array_column($tax_details, 'local_basic')), 2, '.', ',');
			$local_travel_cgst = number_format(array_sum(array_column($tax_details, 'local_cgst')), 2, '.', ',');
			$local_travel_sgst = number_format(array_sum(array_column($tax_details, 'local_sgst')), 2, '.', ',');
			$local_travel_igst = number_format(array_sum(array_column($tax_details, 'local_igst')), 2, '.', ',');
		} else {
			$local_travel_basic = isset($tax_details[0]['local_basic']) ? $tax_details[0]['local_basic'] : 0;
			$local_travel_cgst = isset($tax_details[0]['local_cgst']) ? $tax_details[0]['local_cgst'] : 0;
			$local_travel_sgst = isset($tax_details[0]['local_sgst']) ? $tax_details[0]['local_sgst'] : 0;
			$local_travel_igst = isset($tax_details[0]['local_igst']) ? $tax_details[0]['local_igst'] : 0;
		}
		$transport_total = $lodging_total = $boarding_total = $local_travel_total = 0;
		$transport_total = floatval(preg_replace('/[^\d.]/', '', $transport_basic)) + floatval(preg_replace('/[^\d.]/', '', $transport_cgst)) + floatval(preg_replace('/[^\d.]/', '', $transport_sgst)) + floatval(preg_replace('/[^\d.]/', '', $transport_igst));
		$lodging_total = floatval(preg_replace('/[^\d.]/', '', $lodging_basic)) + floatval(preg_replace('/[^\d.]/', '', $lodging_cgst)) + floatval(preg_replace('/[^\d.]/', '', $lodging_sgst)) + floatval(preg_replace('/[^\d.]/', '', $lodging_igst));
		// Adding round off value
		$lodging_total = floatval(preg_replace('/[^\d.]/', '', $lodging_total)) + floatval(preg_replace('/[^\d.]/', '', $lodging_round_off));
		$boarding_total = floatval(preg_replace('/[^\d.]/', '', $boarding_basic)) + floatval(preg_replace('/[^\d.]/', '', $boarding_cgst)) + floatval(preg_replace('/[^\d.]/', '', $boarding_sgst)) + floatval(preg_replace('/[^\d.]/', '', $boarding_igst));
		$local_travel_total = floatval(preg_replace('/[^\d.]/', '', $local_travel_basic)) + floatval(preg_replace('/[^\d.]/', '', $local_travel_cgst)) + floatval(preg_replace('/[^\d.]/', '', $local_travel_sgst)) + floatval(preg_replace('/[^\d.]/', '', $local_travel_igst));
		$trip->employee_gst_code = '';
		if ($trip && $trip->employee && $trip->employee->outlet && $trip->employee->outlet->address && $trip->employee->outlet->address->city && $trip->employee->outlet->address->city->state) {
			$trip->employee_gst_code = $trip->employee->outlet->address->city->state->gstin_state_code;
		}
		$state_code = NState::leftJoin('ncities', 'ncities.state_id', 'nstates.id')
			->leftJoin('ey_addresses', 'ey_addresses.city_id', 'ncities.id')
			->where('ey_addresses.address_of_id', 3160)
			->where('ey_addresses.entity_id', $trip->outlet_id)
			->pluck('nstates.gstin_state_code')->first();

		$trip->transport_basic = $transport_basic;
		$trip->transport_cgst = $transport_cgst;
		$trip->transport_sgst = $transport_sgst;
		$trip->transport_igst = $transport_igst;
		$trip->lodging_basic = $lodging_basic;
		$trip->lodging_cgst = $lodging_cgst;
		$trip->lodging_sgst = $lodging_sgst;
		$trip->lodging_igst = $lodging_igst;
		$trip->lodging_round_off = $lodging_round_off;
		$trip->boarding_basic = $boarding_basic;
		$trip->boarding_cgst = $boarding_cgst;
		$trip->boarding_sgst = $boarding_sgst;
		$trip->boarding_igst = $boarding_igst;
		$trip->local_travel_basic = $local_travel_basic;
		$trip->local_travel_cgst = $local_travel_cgst;
		$trip->local_travel_sgst = $local_travel_sgst;
		$trip->local_travel_igst = $local_travel_igst;
		$trip->transport_total = number_format($transport_total, 2, '.', '');
		$trip->lodging_total = number_format($lodging_total, 2, '.', '');
		$trip->boarding_total = number_format($boarding_total, 2, '.', '');
		$trip->local_travel_total = number_format($local_travel_total, 2, '.', '');
		$trip->emp_trip_count = $emp_trip_count;
		$trip->emp_claim_amount = $emp_claim_amount;
		$trip->transport_amount = $transport_amount;
		$trip->lodging_amount = $lodging_amount;
		$trip->boarding_amount = $boarding_amount;
		$trip->local_travel_amount = $local_travel_amount;
		$trip->emp_amount_financial_year_from = $emp_amount_financial_year_from;
		$trip->emp_amount_financial_year_to = $emp_amount_financial_year_to;
		$data['trip'] = $trip;
		$data['trip_justify'] = 0;
		$data['state_code'] = $state_code;

		if ($trip->employee->tripEmployeeClaim) {
			if (($trip->employee->tripEmployeeClaim->is_justify_my_trip == 1) || ($trip->employee->tripEmployeeClaim->remarks != '')) {
				$data['trip_justify'] = 1;
			}
		}

		$data['approval_status'] = Trip::validateAttachment($trip_id);
		$data['view'] = URL::asset('public/img/content/yatra/table/view.svg');

		return response()->json($data);
	}

	public static function saveEYatraTripClaim($request) {
		// dd($request->all());
		//validation
		try {
			// $validator = Validator::make($request->all(), [
			// 	'purpose_id' => [
			// 		'required',
			// 	],
			// ]);
			// if ($validator->fails()) {
			// 	return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			// }
			DB::beginTransaction();
			$mode_two_wheeler = false;
			$mode_four_wheeler = false;
			$two_wheeler_total_km = 0;
			$four_wheeler_total_km = 0;

			// if (isset($request->is_attachment_trip) && $request->is_attachment_trip) {
			// 	if (floatval($request->claim_total_amount) <= 0) {
			// 		return response()->json([
			// 			'success' => false,
			// 			'errors' => ['Trip claim amount should be greater than 0'],
			// 		]);
			// 	}
			// }

			// dd($request->all());
			//starting ending Km validation
			if (!empty($request->visits)) {
				$visit_id = Visit::select('id')->where('trip_id', $request->trip_id)->count();
				$two_wheeler_count = Visit::select('travel_mode_id')->where('trip_id', $request->trip_id)->where('travel_mode_id', '=', 15)->count();
				$four_wheeler_count = Visit::select('travel_mode_id')->where('trip_id', $request->trip_id)->where('travel_mode_id', '=', 16)->count();
				$visit_proof_upload_value = Config::where('id', 3983)->first()->name;

				$tripData = Trip::find($request->trip_id);
				if (!empty($tripData->employee->grade_id)) {
					$employeeGradeInfo = DB::table('grade_advanced_eligibility')->select([
						'id',
						'two_wheeler_limit',
						'four_wheeler_limit',
					])
						->where('grade_id', $tripData->employee->grade_id)
						->first();

					$twoWheelerPerDayKmLimit = $employeeGradeInfo ? $employeeGradeInfo->two_wheeler_limit : null;
					$fourWheelerPerDayKmLimit = $employeeGradeInfo ? $employeeGradeInfo->four_wheeler_limit : null;
				} else {
					$twoWheelerPerDayKmLimit = null;
					$fourWheelerPerDayKmLimit = null;
				}

				if ($visit_id >= 2 && $two_wheeler_count >= 2) {
					$validate_end_km = 0;
					//dd($validate_end_km);
					foreach ($request->visits as $visit_key => $visit_val) {
						if ($visit_key == 0) {
							$validate_end_km = $visit_val['km_end'];
						}
						// if ($visit_key > 0 && $validate_end_km > $visit_val['km_start'] && $visit_val['travel_mode_id'] == 15) {
						if ($visit_key > 0 && $validate_end_km > $visit_val['km_start'] && isset($visit_val['travel_mode_id']) && $visit_val['travel_mode_id'] == 15) {
							return response()->json(['success' => false, 'errors' => ['Start KM should be grater then previous end KM']]);
						}
					}
				}
				if ($visit_id >= 2 && $four_wheeler_count >= 2) {
					$validate_end_km = 0;
					foreach ($request->visits as $visit_key => $visit_val) {
						if ($visit_key == 0) {
							$validate_end_km = $visit_val['km_end'];
						}
						// if ($visit_key > 0 && $validate_end_km > $visit_val['km_start'] && $visit_val['travel_mode_id'] == 16) {
						if ($visit_key > 0 && $validate_end_km > $visit_val['km_start'] && isset($visit_val['travel_mode_id']) && $visit_val['travel_mode_id'] == 16) {
							return response()->json(['success' => false, 'errors' => ['Start KM should be grater then previous end KM']]);
						}
					}
				}

				foreach ($request->visits as $visit_info) {
					// //IF AGENT BOOKING VISIT MEANS PROFF UPLOAD SHOULD BE YES
					// if($agent_booking_visit_proof_upload_value == 'Yes' && $visit_info['booked_by'] == 'Agent' && $visit_info['attachment_status'] == 'No'){
					// 	return response()->json([
					// 		'success' => false,
					// 		'errors' => ['Proof upload should be "Yes" for Agent booking visit']
					// 	]);
					// }

					//PROFF UPLOAD SHOULD BE YES VALIDATION
					// if($visit_proof_upload_value == 'Yes' && $visit_info['attachment_status'] == 'No'){
					// if($visit_proof_upload_value == 'Yes' && $visit_info['attachment_status'] == 'No' && !in_array($visit_info['travel_mode_id'], [15,16,17,270,271,272])){
					// 	return response()->json([
					// 		'success' => false,
					// 		'errors' => ['Proof upload should be "Yes" for fare detail']
					// 	]);
					// }

					if($visit_info['booked_by'] == 'Agent'){
						//AGENT
						$agent_visit_travel_mode_id = Visit::where('id', $visit_info['id'])->pluck('travel_mode_id')->first();
						if($visit_proof_upload_value == 'Yes' && $visit_info['attachment_status'] == 'No' && !in_array($agent_visit_travel_mode_id, [15,16,17,270,271,272])){
							return response()->json([
								'success' => false,
								'errors' => ['Proof upload should be "Yes" for fare detail']
							]);
						}
					}else{
						//SELF
						if($visit_proof_upload_value == 'Yes' && $visit_info['attachment_status'] == 'No' && !in_array($visit_info['travel_mode_id'], [15,16,17,270,271,272])){
							return response()->json([
								'success' => false,
								'errors' => ['Proof upload should be "Yes" for fare detail']
							]);
						}
					}

					if (isset($visit_info['travel_mode_id']) && ($visit_info['travel_mode_id'] == 15 || $visit_info['travel_mode_id'] == 16)) {
						if ($visit_info['travel_mode_id'] == 15) {
							//TWO WHEELER
							// $mode_two_wheeler = true;
							// $two_wheeler_total_km += ($visit_info['km_end'] - $visit_info['km_start']);

							$visitKm = ($visit_info['km_end'] - $visit_info['km_start']);
							$visitDateDiff = strtotime($visit_info['arrival_date']) - strtotime($visit_info['departure_date']);
							$visitNoOfDays = ($visitDateDiff / (60 * 60 * 24)) + 1;
							$perDayVisitKm = ($visitKm / $visitNoOfDays);
							if (empty($twoWheelerPerDayKmLimit)) {
								return response()->json([
									'success' => false,
									'errors' => ['Two wheeler KM limit is not updated kindly contact Admin'],
								]);
							}

							if (round($perDayVisitKm) > round($twoWheelerPerDayKmLimit)) {
								return response()->json([
									'success' => false,
									'errors' => ['Two wheeler total KM should be less than or equal to Two wheeler total KM limit : ' . $twoWheelerPerDayKmLimit],
								]);
							}
						}

						if ($visit_info['travel_mode_id'] == 16) {
							//FOUR WHEELER
							// $mode_four_wheeler = true;
							// $four_wheeler_total_km += ($visit_info['km_end'] - $visit_info['km_start']);
							$visitKm = ($visit_info['km_end'] - $visit_info['km_start']);
							$visitDateDiff = strtotime($visit_info['arrival_date']) - strtotime($visit_info['departure_date']);
							$visitNoOfDays = ($visitDateDiff / (60 * 60 * 24)) + 1;
							$perDayVisitKm = ($visitKm / $visitNoOfDays);
							if (empty($fourWheelerPerDayKmLimit)) {
								return response()->json([
									'success' => false,
									'errors' => ['Four wheeler KM limit is not updated kindly contact Admin'],
								]);
							}

							if (round($perDayVisitKm) > round($fourWheelerPerDayKmLimit)) {
								return response()->json([
									'success' => false,
									'errors' => ['Four wheeler total KM should be less than or equal to Four wheeler total KM limit : ' . $fourWheelerPerDayKmLimit],
								]);
							}
						}
					}
				}
			}
			//starting ending km validation

			// dd($request->trip_id);
			if (empty($request->trip_id)) {
				return response()->json(['success' => false, 'errors' => ['Trip not found']]);
			}

			$trip = Trip::find($request->trip_id);
			if (!$trip) {
				return response()->json(['success' => false, 'errors' => ['Trip not found']]);
			}

			//TWO WHEELER AND FOUR WHEELER TOTAL KM VALIDATION
			// if (($mode_two_wheeler == true || $mode_four_wheeler == true) && !empty($trip->employee->grade_id)) {
			// 	$employee_grade_data = DB::table('grade_advanced_eligibility')->select([
			// 		'id',
			// 		'two_wheeler_limit',
			// 		'four_wheeler_limit',
			// 	])
			// 		->where('grade_id', $trip->employee->grade_id)
			// 		->first();

			// 	// $two_wheeler_km_limit = $employee_grade_data ? $employee_grade_data->two_wheeler_limit : 0;
			// 	$four_wheeler_km_limit = $employee_grade_data ? $employee_grade_data->four_wheeler_limit : 0;

			// 	// if($mode_two_wheeler == true){
			// 	// 	if(empty($two_wheeler_km_limit)){
			// 	// 		return response()->json([
			// 	// 			'success' => false,
			// 	// 			'errors' => ['Two wheeler KM limit is not updated kindly contact Admin']
			// 	// 		]);
			// 	// 	}
			// 	// 	if(round($two_wheeler_total_km) > round($two_wheeler_km_limit)){
			// 	// 		return response()->json([
			// 	// 			'success' => false,
			// 	// 			'errors' => ['Two wheeler total KM should be less than or equal to Two wheeler total KM limit : '. $two_wheeler_km_limit]
			// 	// 		]);
			// 	// 	}
			// 	// }

			// 	if ($mode_four_wheeler == true) {
			// 		if (empty($four_wheeler_km_limit)) {
			// 			return response()->json([
			// 				'success' => false,
			// 				'errors' => ['Four wheeler KM limit is not updated kindly contact Admin'],
			// 			]);
			// 		}

			// 		if (round($four_wheeler_total_km) > round($four_wheeler_km_limit)) {
			// 			return response()->json([
			// 				'success' => false,
			// 				'errors' => ['Four wheeler total KM should be less than or equal to Four wheeler total KM limit : ' . $four_wheeler_km_limit],
			// 			]);
			// 		}
			// 	}
			// }

			$isLeaderGrade = GradeAdvancedEligiblity::where('grade_id', $trip->employee->grade_id)->pluck('is_leader_grade')->first();
			$is_grade_leader = false;
			// if (!empty($trip->employee->grade) && in_array($trip->employee->grade->name, ['L1', 'L2', 'L3', 'L4', 'L5', 'L6', 'L7', 'L8', 'L9'])) {
			if (!empty($trip->employee->grade) && $isLeaderGrade == 1) {
				$is_grade_leader = true;
			}

			// Attachment validation by Karthick T on 08-04-2022
			// if (isset($request->is_attachment_trip) && $request->is_attachment_trip) {
			if (isset($request->is_attachment_trip) && $request->is_attachment_trip && $is_grade_leader == false) {
				// dd('final save validation');
				// // Throwing an error if details added with 0 value
				// $employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
				// $errors = [];
				// if (count($trip->visits) > 0 && $employee_claim->transport_total == 0)
				// 	$errors[] = 'Transport amount should be greater than 0';
				// if (count($trip->lodgings) > 0 && $employee_claim->lodging_total == 0)
				// 	$errors[] = 'Lodging amount should be greater than 0';
				// if (count($trip->boardings) > 0 && $employee_claim->boarding_total == 0)
				// 	$errors[] = 'Boarding amount should be greater than 0';
				// if (count($trip->localTravels) > 0 && $employee_claim->local_travel_total == 0)
				// 	$errors[] = 'Local travel amount should be greater than 0';

				// if (count($errors) > 0) return response()->json(['success' => false, 'errors' => $errors]);
				// // Throwing an error if details added with 0 value

				// $is_fare_doc_required_for_visit = Config::where('id', 3982)->first()->name;

				$error_messages = [
					// 'agent_book_visit_fare_detail_doc.required' => 'Agent option selected for ticket booking please attach the ticket selecting the "fare detail" option as attachment.',
					'fare_detail_document_validate.required' => 'Please attach the ticket selecting the "Fare Detail(Agent Ticket)" option for all agent booking fare detail.',
					'self_booking_document_validate.required' => 'Please attach the ticket selecting the "Self Booking Attachments" option for all the self booking fare detail.',
					'fare_detail_doc.required' => 'Fare detail document is required',
					'lodging_doc.required' => 'Lodging document is required',
					'boarding_doc.required' => 'Boarding document is required',
					'other_doc.required' => 'Others document is required',
					'self_booking_doc.required' => 'Self Booking Approval Email is required',
					'toll_fee_doc.required' => 'Toll fee document is required',
					'guest_house_approval_document.required' => 'Guest house approval document is required',
				];
				$validations = [];
				$attachement_types = Attachment::where('attachment_type_id', 3200)
					->where('entity_id', $trip->id)
					->pluck('attachment_of_id')
					->toArray();
				if (!in_array(3750, $attachement_types)) {
					// All Type

					// if($is_fare_doc_required_for_agent_booking_visit == 'Yes'){
					// 	//CHECK FARE DOCUMENT FOR AGENT VISIT
					// 	$agent_booking_visit_count = Visit::where('visits.trip_id', $trip->id)
					// 		->where('visits.attachment_status', 1)
					// 		->where('visits.booking_method_id', 3042) //AGENT
					// 		->count();
					// 	if ($agent_booking_visit_count > 0 && !in_array(3751, $attachement_types)) {
					// 		// Fare Detail Type
					// 		$validations['agent_book_visit_fare_detail_doc'] = 'required';
					// 	}
					// }

					//FARE DETAILS DOCUMENT VALIDATION
					// if($is_fare_doc_required_for_visit == 'Yes'){
					// 	$fare_detail_count = Visit::where('visits.trip_id', $trip->id)
					// 		->whereNotIn('visits.travel_mode_id', [15,16,17,270,271,272])
					// 		->where('visits.attachment_status', 1)
					// 		->count();
					// 	if ($fare_detail_count > 0) {
					// 		if(!in_array(3751, $attachement_types)){
					// 			$validations['fare_detail_document_validate'] = 'required';
					// 		}else{
					// 			$fare_detail_document_count = Attachment::where('attachment_type_id', 3200)
					// 				->where('entity_id', $trip->id)
					// 				->where('attachment_of_id', 3751) //FARE DETAIL
					// 				->count();
					// 			if($fare_detail_document_count < $fare_detail_count){
					// 				$validations['fare_detail_document_validate'] = 'required';
					// 			}
					// 		}
					// 	}
					// }

					$self_fare_detail_count = Visit::where('visits.trip_id', $trip->id)
						->whereNotIn('visits.travel_mode_id', [15,16,17,270,271,272])
						->where('visits.booking_method_id', 3040) //SELF
						->where('visits.attachment_status', 1)
						->count();
					if ($self_fare_detail_count > 0) {
						if(!in_array(3755, $attachement_types)){
							$validations['self_booking_document_validate'] = 'required';
						}else{
							$self_fare_detail_document_count = Attachment::where('attachment_type_id', 3200)
								->where('entity_id', $trip->id)
								->where('attachment_of_id', 3755) //SELF BOOKING ATTACHMENT
								->count();
							if($self_fare_detail_document_count < $self_fare_detail_count){
								$validations['self_booking_document_validate'] = 'required';
							}
						}
					}

					$agent_fare_detail_count = Visit::where('visits.trip_id', $trip->id)
						->whereNotIn('visits.travel_mode_id', [15,16,17,270,271,272])
						->where('visits.booking_method_id', 3042) //AGENT
						->where('visits.attachment_status', 1)
						->count();
					if ($agent_fare_detail_count > 0) {
						if(!in_array(3751, $attachement_types)){
							$validations['fare_detail_document_validate'] = 'required';
						}else{
							$agent_fare_detail_document_count = Attachment::where('attachment_type_id', 3200)
								->where('entity_id', $trip->id)
								->where('attachment_of_id', 3751) //FARE DETAIL
								->count();
							if($agent_fare_detail_document_count < $agent_fare_detail_count){
								$validations['fare_detail_document_validate'] = 'required';
							}
						}
					}


					$checkGuestHouseApprovalAttachment = GradeAdvancedEligiblity::where('grade_id', $trip->employee->grade_id)->pluck('check_guest_house_approval_attachment')->first();

					$lodging_guest_house_cities = Lodging::join('ncities','ncities.id','lodgings.city_id')
						->where('lodgings.trip_id', $trip->id)
						->where('lodgings.attachment_status', 1)
						->where('lodgings.stay_type_id', 3340) //LODGE STAY
						->where('ncities.guest_house_status',1)
						->pluck('ncities.id');
					// if (count($lodging_guest_house_cities) > 0 && !in_array(3756, $attachement_types) && $is_grade_leader == false) {
					// if (count($lodging_guest_house_cities) > 0 && !in_array(3756, $attachement_types) && $is_grade_leader == false) {
					if (count($lodging_guest_house_cities) > 0 && !in_array(3756, $attachement_types) && $is_grade_leader == false && $checkGuestHouseApprovalAttachment == 1) {
						$validations['guest_house_approval_document'] = 'required';
            		}

					$visit_count = Visit::join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')->whereNotIn('visit_bookings.travel_mode_id', [15, 16, 17])->where('visits.trip_id', $trip->id)->where('visits.attachment_status', 1)->count();
					if ($visit_count > 0 && !in_array(3751, $attachement_types)) {
						// Fare Detail Type
						// $validations['fare_detail_doc'] = 'required';
					}

					// $lodging_count = Lodging::where('trip_id', $trip->id)->where('attachment_status', 1)->count();
					$lodging_count = Lodging::where('trip_id', $trip->id)->where('attachment_status', 1)
						->where('stay_type_id', 3340) //LODGE STAY
						->count();
					if ($lodging_count > 0 && !in_array(3752, $attachement_types)) {
						// Loding Type
						$validations['lodging_doc'] = 'required';
					}
					$boarding_count = Boarding::where('trip_id', $trip->id)->where('attachment_status', 1)->count();
					if ($boarding_count > 0 && !in_array(3753, $attachement_types)) {
						// Boarding Type
						$validations['boarding_doc'] = 'required';
					}
					$other_count = LocalTravel::where('trip_id', $trip->id)->where('attachment_status', 1)->count();
					if ($other_count > 0 && !in_array(3754, $attachement_types)) {
						// Others Type
						$validations['other_doc'] = 'required';
					}
					//$self_booking = Visit::where('trip_id', $trip->id)->where('self_booking_approval', 1)->count();
					$self_booking = Visit::join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
						->whereNotIn('visit_bookings.travel_mode_id', [15, 16, 17])
						->where('visits.trip_id', $trip->id)
						->where('visits.self_booking_approval', 1)
						->where('visits.trip_mode_id', 3793) // 3793 -> Overnight
						->count();
					if ($self_booking > 0 && !in_array(3755, $attachement_types)) {
						// Others Type
						// $validations['self_booking_doc'] = 'required';
					}
					// Toll fee doc required
					$tollFeeLocalTravelCount = LocalTravel::where('trip_id', $request->trip_id)
						->where('mode_id', 63) // 63 -> Toll Fee
						->count();
					if ($tollFeeLocalTravelCount > 0 && !in_array(3754, $attachement_types)) {
						// Toll Fee Document
						$validations['toll_fee_doc'] = 'required';
					}
					$validator = Validator::make($request->all(), $validations, $error_messages);

					// // If toll fee exist and attachment not found the claim will go to deviation
					// if ($is_grade_leader == false) {
					// 	$tollFeeAttachmentCount = Attachment::where('attachment_type_id', 3200)
					// 		->whereIn('attachment_of_id', [3750, 3754])	// 3750-> All, 3754->Others
					// 		->where('entity_id', $request->trip_id)
					// 		->count();
					// 	$tollFeeLocalTravelCount = LocalTravel::where('trip_id', $request->trip_id)
					// 		->where('mode_id', 63)	// 63 -> Toll Fee
					// 		->count();
					// 	if ($tollFeeLocalTravelCount > 0 && $tollFeeAttachmentCount == 0) {
					// 		$employee_claim->is_deviation = 1;
					// 	}
					// }
					// If toll fee exist and attachment not found the claim will go to deviation

					if ($validator->fails()) {
						return response()->json([
							'success' => false,
							'message' => 'Validation Errors',
							'errors' => $validator->errors()->all(),
						]);
					}
				}
			}
			// Attachment validation by Karthick T on 08-04-2022

			// $is_grade_leader = false;
			// if(!empty($trip->employee->grade) && in_array($trip->employee->grade->name, ['L1','L2','L3','L4','L5','L6','L7','L8','L9'])){
			// 	$is_grade_leader = true;
			// }

			// Validate Boading amount by Karthick T on 04-08-2022
			if ($request->boardings) {
				foreach ($request->boardings as $boarding_data) {
					// dd($boarding_data);
					$boardingEligibleAmount = (float) $boarding_data['eligible_amount'] * $boarding_data['days'];
					$boardingAmount = (float) $boarding_data['amount'];
					// if ($boardingAmount > $boardingEligibleAmount) {
					// 	return response()->json(['success' => false, 'errors' => ['Boarding amount is not greater than eligible amount']]);
					// }

					if ($is_grade_leader == false) {
						if ($boardingAmount > $boardingEligibleAmount) {
							return response()->json(['success' => false, 'errors' => ['Boarding amount is not greater than eligible amount']]);
						}
					}
				}
			}
			// Validate Boading amount by Karthick T on 04-08-2022

			//Get employee outstion beta amount
			$beta_amount = Employee::join('grade_advanced_eligibility', 'grade_advanced_eligibility.grade_id', 'employees.grade_id')->where('employees.id', $trip->employee_id)->pluck('grade_advanced_eligibility.outstation_trip_amount')->first();

			$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
			if (!$employee_claim->number) {
				$outlet_id = $trip->outlet_id;
				if (!$outlet_id) {
					$outlet_id = (isset(Auth::user()->entity->outlet_id) && Auth::user()->entity->outlet_id) ? Auth::user()->entity->outlet_id : null;
					if (!$outlet_id) {
						return response()->json(['success' => false, 'errors' => ['Outlet not found!']]);
					}

				}

				$financial_year = getFinancialYear();
				$financial_year_id = FinancialYear::where('from', $financial_year)->pluck('id')->first();
				if (!$financial_year_id) {
					return response()->json(['success' => false, 'errors' => ['Financial Year Not Found']]);
				}

				// Outstation Trip Claim
				$get_request_no = SerialNumberGroup::generateNumber(4, $financial_year_id, $outlet_id);
				if (!$get_request_no['success']) {
					return response()->json(['success' => false, 'errors' => ['Serial Number Not Found']]);
				}

				$number = $get_request_no['number'];
				$employee_claim->number = $number;
				if (!$employee_claim->employee_id) {
					$employee_claim->employee_id = Auth::user()->entity_id;
				}

				if (!$employee_claim->status_id) {
					$employee_claim->status_id = 3033;
				}
				//CLAIM INPROGRESS
				if (!$employee_claim->created_by) {
					$employee_claim->created_by = Auth::user()->id;
				}

				if (!$employee_claim->total_amount) {
					$employee_claim->total_amount = 0;
				}

				$employee_claim->save();
			}

			//SAVING VISITS
			if ($request->visits) {

				//REMOVE TRANSPORT ATTACHMENT
				if (!empty($request->transport_attach_removal_ids)) {
					$transport_attach_removal_ids = json_decode($request->transport_attach_removal_ids, true);
					Attachment::whereIn('id', $transport_attach_removal_ids)->delete();
				}

				// dd($request->visits);
				$transport_total_amount = 0;
				foreach ($request->visits as $visit_data) {
					if (!empty($visit_data['id'])) {
						$visit = Visit::find($visit_data['id']);

						//CONCATENATE DATE & TIME
						$depart_date = $visit_data['departure_date'];
						$depart_time = $visit_data['departure_time'];
						$arrival_date = $visit_data['arrival_date'];
						$arrival_time = $visit_data['arrival_time'];
						$visit->departure_date = date('Y-m-d H:i:s', strtotime("$depart_date $depart_time"));
						$visit->arrival_date = date('Y-m-d H:i:s', strtotime("$arrival_date $arrival_time"));

						//GET BOOKING TYPE
						$booked_by = strtolower($visit_data['booked_by']);
						if ($booked_by == 'self') {
							$visit->travel_mode_id = $visit_data['travel_mode_id'];
						}
						$visit->attachment_status = $visit_data['attachment_status'];
						$visit->save();
						// dd($visit_data['id']);

						//UPDATE VISIT BOOKING STATUS ONLY FOR SELF
						if ($booked_by == 'self') {

							//TWO WHEELER OR FOUR WHELLER VALIDATIONS
							if ($visit_data['travel_mode_id'] == '16' || $visit_data['travel_mode_id'] == '15') {
								if (!isset($visit_data['km_start']) || (isset($visit_data['km_start']) && empty($visit_data['km_start']))) {
									return response()->json([
										'success' => false,
										'errors' => [
											'Starting KM is required',
										],
									]);
								}
								if (!isset($visit_data['km_end']) || (isset($visit_data['km_end']) && empty($visit_data['km_end']))) {
									return response()->json([
										'success' => false,
										'errors' => [
											'Ending KM is required',
										],
									]);
								}
								/*if (!isset($visit_data['toll_fee']) || (isset($visit_data['toll_fee']) && empty($visit_data['toll_fee']))) {
									return response()->json([
										'success' => false,
										'errors' => [
											'Toll Fee is required',
										],
									]);
								}*/
							}

							$visit_booking = VisitBooking::firstOrNew(['visit_id' => $visit_data['id']]);
							$visit_booking->visit_id = $visit_data['id'];
							$visit_booking->type_id = 3100;
							$visit_booking->travel_mode_id = $visit_data['travel_mode_id'];
							$visit_booking->reference_number = $visit_data['reference_number'];
							$visit_booking->remarks = $visit_data['remarks'];
							$visit_booking->amount = $visit_data['amount'];
							//$visit_booking->tax = $visit_data['tax'];
							$visit_booking->cgst = $visit_data['cgst'];
							$visit_booking->sgst = $visit_data['sgst'];
							$visit_booking->igst = $visit_data['igst'];
							$visit_booking->tax_percentage = $visit_data['tax_percentage'];
							$visit_booking->invoice_number = $visit_data['invoice_number'];
							$visit_booking->invoice_amount = $visit_data['invoice_amount'];
							$visit_booking->invoice_date = $visit_data['invoice_date'] ? date('Y-m-d', strtotime($visit_data['invoice_date'])) : null;
							if (!empty($visit_data['round_off']) && ($visit_data['round_off'] > 1 || $visit_data['round_off'] < -1)) {
								return response()->json(['success' => false, 'errors' => ['Round off amount limit is +1 Or -1']]);
							} else {
								$visit_booking->round_off = $visit_data['round_off'];
							}
							$visit_booking->other_charges = $visit_data['other_charges'];
							$visit_booking->km_start = (isset($visit_data['km_start']) && !empty($visit_data['km_start'])) ? $visit_data['km_start'] : null;
							$visit_booking->km_end = (isset($visit_data['km_end']) && !empty($visit_data['km_end'])) ? $visit_data['km_end'] : null;
							$visit_booking->toll_fee = (isset($visit_data['toll_fee']) && !empty($visit_data['toll_fee'])) ? $visit_data['toll_fee'] : null;
							$visit_booking->service_charge = '0.00';
							$visit_booking->total = $visit_data['total'];
							$visit_booking->paid_amount = $visit_data['total'];
							$visit_booking->created_by = Auth::user()->id;
							$visit_booking->status_id = 3241; //Claimed
							// $gstin = $visit_data['gstin'];
							$user_company_id = Auth::user()->company_id;
							$gstin_enable = Company::where('id', $user_company_id)->pluck('gstin_enable')->first();
							if ($gstin_enable == 1 && !empty($visit_data['gstin'])) {
								if ($visit_data['travel_mode_id'] != '15' || $visit_data['travel_mode_id'] != '16' || $visit_data['travel_mode_id'] != '17' || $visit_data['travel_mode_id'] != '12') {
									$response = app('App\Http\Controllers\AngularController')->verifyGSTIN($visit_data['gstin'], "", false);
									//dd($response);
									if (!$response['success']) {
										return response()->json([
											'success' => false,
											'errors' => [
												$response['error'],
											],
										]);
									}
									$visit_booking->gstin = $response['gstin'];
									$visit_booking->gstin_name = $visit_data['gstin_name'];
									$visit_booking->gstin_state_code = $visit_data['gstin_state_code'];
									$visit_booking->gstin_address = $visit_data['gstin_address'];
								} else {
									$visit_booking->gstin = NULL;
									$visit_booking->gstin_name = NULL;
									$visit_booking->gstin_state_code = NULL;
									$visit_booking->gstin_address = NULL;
								}
							} else {
								$visit_booking->gstin = NULL;
								$visit_booking->gstin_name = NULL;
								$visit_booking->gstin_state_code = NULL;
								$visit_booking->gstin_address = NULL;
							}
							$visit_booking->save();
							$transport_total = 0;
							if ($visit_booking) {
								$transport_total = $visit_booking->amount + $visit_booking->cgst + $visit_booking->sgst + $visit_booking->igst + $visit_booking->toll_fee + $visit_booking->round_off + $visit_booking->other_charges;
								$transport_total_amount += $transport_total;
							}
						}

						// dd($visit_booking);

					}
				}

				//SAVE TRANSPORT ATTACHMENT
				$item_images = storage_path('app/public/trip/transport/attachments/');
				Storage::makeDirectory($item_images, 0777);
				if (!empty($request->transport_attachments)) {
					foreach ($request->transport_attachments as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $request->trip_id . '_transport_attachment' . $value . '.' . $extension;
						$attachement->move(storage_path('app/public/trip/transport/attachments/'), $name);
						$attachement_transport = new Attachment;
						$attachement_transport->attachment_of_id = 3189;
						$attachement_transport->attachment_type_id = 3200;
						$attachement_transport->entity_id = $request->trip_id;
						$attachement_transport->name = $name;
						$attachement_transport->save();
					}
				}

				//CHECK NEXT VISIT EXIST
				//ONLY SELF VISITS WILL COME IN POST NOT AGENT BOOKED ==> NOT BEEN USED NOW

				// $lodge_checkin_out_date_range_list = array();
				// $trip = Trip::with(
				// 	['visits' => function ($q) {
				// 		$q->orderBy('id', 'asc');
				// 	},
				// 	])->find($request->trip_id);
				// foreach ($trip->visits as $visit_data_key => $visit_data_val) {
				// 	$next_visit = $visit_data_key;
				// 	$next_visit++;
				// 	//LODGE CHECK IN & OUT DATE LIST
				// 	if (isset($trip->visits[$next_visit])) {
				// 		$date_range = Trip::getDatesFromRange($visit_data_val['departure_date'], $trip->visits[$next_visit]['departure_date']);
				// 		if (!empty($date_range)) {
				// 			$lodge_checkin_out_date_range_list[$visit_data_key][0]['id'] = '';
				// 			$lodge_checkin_out_date_range_list[$visit_data_key][0]['name'] = 'Select Date';
				// 			foreach ($date_range as $range_key => $range_val) {
				// 				$range_key++;
				// 				$lodge_checkin_out_date_range_list[$visit_data_key][$range_key]['id'] = $range_val;
				// 				$lodge_checkin_out_date_range_list[$visit_data_key][$range_key]['name'] = $range_val;
				// 			}
				// 		}
				// 	}
				// }

				//SAVE EMPLOYEE CLAIMS
				$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
				$employee_claim->trip_id = $trip->id;
				$employee_claim->transport_total = $transport_total_amount;
				$employee_claim->employee_id = Auth::user()->entity_id;
				$employee_claim->status_id = 3033; //CLAIM INPROGRESS
				$employee_claim->created_by = Auth::user()->id;
				$employee_claim->total_amount = 0;
				$employee_claim->save();

				$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
				$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
				$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
				$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
				$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;

				//Check Beta Amount
				if ($lodging_amount == 0 && $boarding_amount == 0) {
					$employee_beta_amount = $beta_amount * $request->trip_total_days;
					$total_amount += $employee_beta_amount;

					$employee_claim->beta_amount = $employee_beta_amount;
				} else {
					$employee_claim->beta_amount = NULL;
				}

				$employee_claim->total_trip_days = $request->trip_total_days;
				$employee_claim->total_amount = $total_amount;

				//To Find Amount to Pay Financier or Employee
				if ($trip->advance_received) {
					if ($trip->advance_received > $total_amount) {
						$balance_amount = $trip->advance_received - $total_amount;
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 2;
					} else {
						$balance_amount = $total_amount - $trip->advance_received;
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 1;
					}
				} else {
					$employee_claim->balance_amount = $total_amount ? $total_amount : 0;
					$employee_claim->amount_to_pay = 1;
				}

				// dump($trip->advance_received);
				// dump($request->claim_total_amount);

				$employee_claim->save();

				$trip->status_id = 3033; //CLAIM INPROGRESS
				$trip->save();

				DB::commit();
				// dd($employee_claim);
				return response()->json(['success' => true]);
			}

			//SAVING LODGINGS
			if ($request->is_lodging) {
				//dd($request->all());
				//REMOVE LODGING AND THIER ATTACHMENTS
				if (!empty($request->lodgings_removal_id)) {
					$lodgings_removal_id = json_decode($request->lodgings_removal_id, true);
					Lodging::whereIn('id', $lodgings_removal_id)->delete();
					// Attachment::whereIn('entity_id', $lodgings_removal_id)->delete();
				}

				// dd($request->lodgings);
				//SAVE
				$loding_attachment_exist = true;
				if ($request->lodgings) {
					$loding_attachment_exist = false;
					// dd($request->lodgings);
					// LODGE STAY DAYS SHOULD NOT EXCEED TOTAL TRIP DAYS
					$lodge_stayed_days = (int) array_sum(array_column($request->lodgings, 'stayed_days'));
					$trip_total_days = (int) $request->trip_total_days;
					if ($lodge_stayed_days > $trip_total_days) {
						return response()->json(['success' => false, 'errors' => ['Total lodging days should be less than total trip days']]);
					}

					//Delete previous entries
					$lodgings = Lodging::where('trip_id', $request->trip_id)->forceDelete();

					$lodging_unique_share_with_claims = [];
					$lodging_share_with_claims = [];
					foreach ($request->lodgings as $lodge_info) {
						if ($lodge_info['sharing_type_id'] == 3811) {
							//SHARING WITH CLAIM
							if (isset($lodge_info['gstin']) && isset($lodge_info['reference_number']) && isset($lodge_info['invoice_date'])) {
								$logding_row = $lodge_info['gstin'] . "|" . $lodge_info['reference_number'] . "|" . $lodge_info['invoice_date'];
								isset($lodging_unique_share_with_claims[$logding_row]) or $lodging_unique_share_with_claims[$logding_row] = $lodge_info;
							}
							$lodging_share_with_claims[] = $lodge_info;
						}
					}

					// if(count($lodging_unique_share_with_claims) > 0  && count($request->lodgings) != count(array_values($lodging_unique_share_with_claims))){
					if ((count($lodging_share_with_claims) > 0 && count($lodging_unique_share_with_claims) > 0) && (count($lodging_share_with_claims) != count(array_values($lodging_unique_share_with_claims)))) {
						return response()->json([
							'success' => false,
							'errors' => ['Cannot enter the same GSTIN, Invoice number, Invoice Date for sharing claim'],
						]);
					}

					$lodging_total_amount = 0;
					foreach ($request->lodgings as $lodgeKey => $lodging_data) {

						if (isset($lodging_data['id'])) {
							$lodging = Lodging::where('id', $lodging_data['id'])->first();
							if (!$lodging) {
								$lodging = new Lodging;
							}
						} else {
							$lodging = new Lodging;
						}
						//dd($lodging_data['lodge_name']);
						// if ($lodging_data['amount'] > 0 && $lodging_data['stay_type_id'] == 3340) {
						// if ($lodging_data['amount'] > 1000 && $lodging_data['stay_type_id'] == 3340) {
						$lodge_check_in_date = $lodging_data['check_in_date'];
						$lodge_check_in_time = $lodging_data['check_in_time'];
						$lodge_checkout_date = $lodging_data['checkout_date'];
						$lodge_checkout_time = $lodging_data['checkout_time'];
						$lodging_check_in_date_time = date('Y-m-d H:i:s', strtotime("$lodge_check_in_date $lodge_check_in_time"));
						$lodging_checkout_date_time = date('Y-m-d H:i:s', strtotime("$lodge_checkout_date $lodge_checkout_time"));
						if($lodging_checkout_date_time <= $lodging_check_in_date_time){
							return response()->json([
								'success' => false,
								'errors' => ['Lodging check out date time should be greater than the check in date time'],
							]);
						}


						if ($lodging_data['stayed_days'] && $lodging_data['stayed_days'] > 0) {
							$lodge_per_day_amt = $lodging_data['amount'] / $lodging_data['stayed_days'];
						} else {
							$lodge_per_day_amt = $lodging_data['amount'];
						}

						if ($lodge_per_day_amt > 1000 && $lodging_data['stay_type_id'] == 3340) {
							if (empty($lodging_data['lodge_name'])) {
								$response = app('App\Http\Controllers\AngularController')->verifyGSTIN($lodging_data['gstin'], $lodging_data['lodge_name'], true);
								if (!$response['success']) {
									return response()->json([
										'success' => false,
										'errors' => [
											$response['error'],
										],
									]);
								}
								$lodging->lodge_name = $lodging_data['gstin'];
								$lodging->gstin = $lodging_data['lodge_name'];
								$lodging->gstin_state_code = $lodging_data['gstin_state_code'];
								$lodging->gstin_address = $lodging_data['gstin_address'];
							}
						} else {
							$lodging->gstin = null;
							$lodging->lodge_name = null;
							$lodging->gstin_state_code = null;
							$lodging->gstin_address = null;
						}
						// if($lodging_data['stay_type_id'] == 3340 || $lodging_data['stay_type_id'] == 3341){
						if ($lodging_data['stay_type_id'] == 3340) {
							if ($lodging_data['amount'] == 0) {
								return response()->json(['success' => false, 'errors' => ['Please Enter Before Tax Amount']]);
							}
						}
						if (!empty($lodging_data['round_off']) && ($lodging_data['round_off'] > 1 || $lodging_data['round_off'] < -1)) {
							return response()->json(['success' => false, 'errors' => ['Round off amount limit is +1 Or -1']]);
						}
						$lodging->fill($lodging_data);
						if ($lodging_data['stay_type_id'] == 3342) {
							$lodging->amount = 0.00;
						}
						$lodging->trip_id = $request->trip_id;

						//CONCATENATE DATE & TIME
						$check_in_date = $lodging_data['check_in_date'];
						$check_in_time = $lodging_data['check_in_time'];
						$checkout_date = $lodging_data['checkout_date'];
						$checkout_time = $lodging_data['checkout_time'];
						$lodging->check_in_date = date('Y-m-d H:i:s', strtotime("$check_in_date $check_in_time"));
						$lodging->checkout_date = date('Y-m-d H:i:s', strtotime("$checkout_date $checkout_time"));
						$lodging->reference_number = (isset($lodging_data['reference_number']) && !empty($lodging_data['reference_number'])) ? $lodging_data['reference_number'] : null;
						$invoice_date = (isset($lodging_data['invoice_date']) && !empty($lodging_data['invoice_date'])) ? $lodging_data['invoice_date'] : null;
						$lodging->invoice_date = date('Y-m-d', strtotime($invoice_date));
						$lodging->created_by = Auth::user()->id;
						$lodging->has_multiple_tax_invoice = 0;
						$lodging->tax_invoice_amount = NULL;
						$lodging->no_of_sharing = $lodging_data['no_of_sharing'];
						$lodging->sharing_type_id = $lodging_data['sharing_type_id'];
						$lodging->save();

						$lodging_total = 0;
						if ($lodging) {
							$lodgingAmount = ($lodging->amount && $lodging->amount != 'NaN') ? $lodging->amount : 0;
							$lodgingCgstAmount = ($lodging->cgst && $lodging->cgst != 'NaN') ? $lodging->cgst : 0;
							$lodgingSgstAmount = ($lodging->sgst && $lodging->sgst != 'NaN') ? $lodging->sgst : 0;
							$lodgingIgstAmount = ($lodging->igst && $lodging->igst != 'NaN') ? $lodging->igst : 0;
							$lodgingRoundOffAmount = ($lodging->round_off && $lodging->round_off != 'NaN') ? $lodging->round_off : 0;
							// $lodging_total = $lodging->amount + $lodging->cgst + $lodging->sgst + $lodging->igst + $lodging->round_off;
							$lodging_total = $lodgingAmount + $lodgingCgstAmount + $lodgingSgstAmount + $lodgingIgstAmount + $lodgingRoundOffAmount;
							$lodging_total_amount += $lodging_total;
						}

						//LODGE TAX INVOICE SAVE

						$lodging->taxInvoices()->forceDelete();

						//ONLY FOR LODGE STAY TYPE
						if ($lodging_data['stay_type_id'] == '3340' && $lodging_data['has_multiple_tax_invoice'] == "Yes") {
							if (empty($lodging_data['tax_invoice_amount'])) {
								return response()->json([
									'success' => false,
									'errors' => [
										"Lodge tax invoice amount is required. Kindly fill in the tax invoice details form.",
									],
								]);
							}
							// if (floatval($lodging_data['invoice_amount']) != floatval($lodging_data['total'])) {
							if (round($lodging_data['invoice_amount']) != round($lodging_data['total'])) {
								return response()->json([
									'success' => false,
									'errors' => [
										"Lodge invoice amount not matched with the total amount",
									],
								]);
							}

							$lodging->has_multiple_tax_invoice = 1;
							$lodging->tax_invoice_amount = !empty($lodging_data['tax_invoice_amount']) ? $lodging_data['tax_invoice_amount'] : NULL;
							$lodging->save();

							//SAVE LODGE TAX INVOICE
							$lodgeTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3771,
							]);
							//NEW
							if (!$lodgeTaxInvoice->exists) {
								$lodgeTaxInvoice->created_at = Carbon::now();
							} else {
								$lodgeTaxInvoice->updated_at = Carbon::now();
							}
							$lodgeTaxInvoice->without_tax_amount = !empty($lodging_data['lodgingTaxInvoice']['without_tax_amount']) ? $lodging_data['lodgingTaxInvoice']['without_tax_amount'] : 0;
							$lodgeTaxInvoice->tax_percentage = !empty($lodging_data['lodgingTaxInvoice']['tax_percentage']) ? $lodging_data['lodgingTaxInvoice']['tax_percentage'] : 0;
							$lodgeTaxInvoice->cgst = !empty($lodging_data['lodgingTaxInvoice']['cgst']) ? $lodging_data['lodgingTaxInvoice']['cgst'] : 0;
							$lodgeTaxInvoice->sgst = !empty($lodging_data['lodgingTaxInvoice']['sgst']) ? $lodging_data['lodgingTaxInvoice']['sgst'] : 0;
							$lodgeTaxInvoice->igst = !empty($lodging_data['lodgingTaxInvoice']['igst']) ? $lodging_data['lodgingTaxInvoice']['igst'] : 0;
							$lodgeTaxInvoice->total = !empty($lodging_data['lodgingTaxInvoice']['total']) ? $lodging_data['lodgingTaxInvoice']['total'] : 0;
							$lodgeTaxInvoice->save();

							//SAVE DRYWASH TAX INVOICE
							$drywashTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3772,
							]);
							//NEW
							if (!$drywashTaxInvoice->exists) {
								$drywashTaxInvoice->created_at = Carbon::now();
							} else {
								$drywashTaxInvoice->updated_at = Carbon::now();
							}
							$drywashTaxInvoice->without_tax_amount = !empty($lodging_data['drywashTaxInvoice']['without_tax_amount']) ? $lodging_data['drywashTaxInvoice']['without_tax_amount'] : 0;
							$drywashTaxInvoice->tax_percentage = !empty($lodging_data['drywashTaxInvoice']['tax_percentage']) ? $lodging_data['drywashTaxInvoice']['tax_percentage'] : 0;
							$drywashTaxInvoice->cgst = !empty($lodging_data['drywashTaxInvoice']['cgst']) ? $lodging_data['drywashTaxInvoice']['cgst'] : 0;
							$drywashTaxInvoice->sgst = !empty($lodging_data['drywashTaxInvoice']['sgst']) ? $lodging_data['drywashTaxInvoice']['sgst'] : 0;
							$drywashTaxInvoice->igst = !empty($lodging_data['drywashTaxInvoice']['igst']) ? $lodging_data['drywashTaxInvoice']['igst'] : 0;
							$drywashTaxInvoice->total = !empty($lodging_data['drywashTaxInvoice']['total']) ? $lodging_data['drywashTaxInvoice']['total'] : 0;
							$drywashTaxInvoice->save();

							//SAVE BOARDING TAX INVOICE
							$boardingTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3773,
							]);
							//NEW
							if (!$boardingTaxInvoice->exists) {
								$boardingTaxInvoice->created_at = Carbon::now();
							} else {
								$boardingTaxInvoice->updated_at = Carbon::now();
							}
							$boardingTaxInvoice->without_tax_amount = !empty($lodging_data['boardingTaxInvoice']['without_tax_amount']) ? $lodging_data['boardingTaxInvoice']['without_tax_amount'] : 0;
							$boardingTaxInvoice->tax_percentage = !empty($lodging_data['boardingTaxInvoice']['tax_percentage']) ? $lodging_data['boardingTaxInvoice']['tax_percentage'] : 0;
							$boardingTaxInvoice->cgst = !empty($lodging_data['boardingTaxInvoice']['cgst']) ? $lodging_data['boardingTaxInvoice']['cgst'] : 0;
							$boardingTaxInvoice->sgst = !empty($lodging_data['boardingTaxInvoice']['sgst']) ? $lodging_data['boardingTaxInvoice']['sgst'] : 0;
							$boardingTaxInvoice->igst = !empty($lodging_data['boardingTaxInvoice']['igst']) ? $lodging_data['boardingTaxInvoice']['igst'] : 0;
							$boardingTaxInvoice->total = !empty($lodging_data['boardingTaxInvoice']['total']) ? $lodging_data['boardingTaxInvoice']['total'] : 0;
							$boardingTaxInvoice->save();

							//SAVE OTHERS TAX INVOICE
							$othersTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3774,
							]);
							//NEW
							if (!$othersTaxInvoice->exists) {
								$othersTaxInvoice->created_at = Carbon::now();
							} else {
								$othersTaxInvoice->updated_at = Carbon::now();
							}
							$othersTaxInvoice->without_tax_amount = !empty($lodging_data['othersTaxInvoice']['without_tax_amount']) ? $lodging_data['othersTaxInvoice']['without_tax_amount'] : 0;
							$othersTaxInvoice->tax_percentage = !empty($lodging_data['othersTaxInvoice']['tax_percentage']) ? $lodging_data['othersTaxInvoice']['tax_percentage'] : 0;
							$othersTaxInvoice->cgst = !empty($lodging_data['othersTaxInvoice']['cgst']) ? $lodging_data['othersTaxInvoice']['cgst'] : 0;
							$othersTaxInvoice->sgst = !empty($lodging_data['othersTaxInvoice']['sgst']) ? $lodging_data['othersTaxInvoice']['sgst'] : 0;
							$othersTaxInvoice->igst = !empty($lodging_data['othersTaxInvoice']['igst']) ? $lodging_data['othersTaxInvoice']['igst'] : 0;
							$othersTaxInvoice->total = !empty($lodging_data['othersTaxInvoice']['total']) ? $lodging_data['othersTaxInvoice']['total'] : 0;
							$othersTaxInvoice->save();

							//SAVE DISCOUNT TAX INVOICE
							$discountTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3776,
							]);
							if (!$discountTaxInvoice->exists) {
								$discountTaxInvoice->created_at = Carbon::now();
							} else {
								$discountTaxInvoice->updated_at = Carbon::now();
							}
							$discountTaxInvoice->without_tax_amount = !empty($lodging_data['discountTaxInvoice']['without_tax_amount']) ? $lodging_data['discountTaxInvoice']['without_tax_amount'] : 0;
							$discountTaxInvoice->tax_percentage = !empty($lodging_data['discountTaxInvoice']['tax_percentage']) ? $lodging_data['discountTaxInvoice']['tax_percentage'] : 0;
							$discountTaxInvoice->cgst = !empty($lodging_data['discountTaxInvoice']['cgst']) ? $lodging_data['discountTaxInvoice']['cgst'] : 0;
							$discountTaxInvoice->sgst = !empty($lodging_data['discountTaxInvoice']['sgst']) ? $lodging_data['discountTaxInvoice']['sgst'] : 0;
							$discountTaxInvoice->igst = !empty($lodging_data['discountTaxInvoice']['igst']) ? $lodging_data['discountTaxInvoice']['igst'] : 0;
							$discountTaxInvoice->total = !empty($lodging_data['discountTaxInvoice']['total']) ? $lodging_data['discountTaxInvoice']['total'] : 0;
							$discountTaxInvoice->save();

							//SAVE ROUNDOFF TAX INVOICE
							$roundoffTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3775,
							]);
							//NEW
							if (!$roundoffTaxInvoice->exists) {
								$roundoffTaxInvoice->created_at = Carbon::now();
							} else {
								$roundoffTaxInvoice->updated_at = Carbon::now();
							}
							$roundoffTaxInvoice->without_tax_amount = !empty($lodging_data['roundoffTaxInvoice']['without_tax_amount']) ? $lodging_data['roundoffTaxInvoice']['without_tax_amount'] : 0;
							$roundoffTaxInvoice->cgst = !empty($lodging_data['roundoffTaxInvoice']['cgst']) ? $lodging_data['roundoffTaxInvoice']['cgst'] : 0;
							$roundoffTaxInvoice->sgst = !empty($lodging_data['roundoffTaxInvoice']['sgst']) ? $lodging_data['roundoffTaxInvoice']['sgst'] : 0;
							$roundoffTaxInvoice->igst = !empty($lodging_data['roundoffTaxInvoice']['igst']) ? $lodging_data['roundoffTaxInvoice']['igst'] : 0;
							$roundoffTaxInvoice->total = !empty($lodging_data['roundoffTaxInvoice']['total']) ? $lodging_data['roundoffTaxInvoice']['total'] : 0;
							$roundoffTaxInvoice->save();
						}

						//LODGE SHARE SAVE
						if ($lodging_data['sharing_type_id'] == 3811) {
							//SHARING WITH CLAIM

							if (isset($lodging_data['gstin']) && isset($lodging_data['reference_number']) && isset($lodging_data['invoice_date'])) {
								$claim_share_exist_check = Lodging::select([
									'lodgings.id',
									'ey_employee_claims.number',
								])
									->join('trips', 'trips.id', 'lodgings.trip_id')
									->join('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id')
									->where('lodgings.trip_id', '!=', $request->trip_id)
									->where('lodgings.gstin', $lodging_data['gstin'])
									->where('lodgings.reference_number', $lodging_data['reference_number'])
									->where('lodgings.invoice_date', date('Y-m-d', strtotime($lodging_data['invoice_date'])))
									->whereNotIn('trips.status_id', [3032, 3038, 3022]) //CANCEL , AUTO CANCEL, MANAGER REJECTED
									->whereNotIn('ey_employee_claims.status_id', [3032, 3039, 3024]) //CANCEL, AUTO CANCEL, CLAIM REJECTED
									->where('lodgings.sharing_type_id', 3811) //SHARING WITH CLAIM
									->first();

								if (!empty($claim_share_exist_check)) {
									return response()->json([
										'success' => false,
										'errors' => ['Claim already shared for this details. Claim number : ' . $claim_share_exist_check->number],
									]);
								}
							}

							$lodge_share_details = json_decode($lodging_data['sharing_employees'], true);
							if (empty($lodge_share_details) || count($lodge_share_details) == 0) {
								return response()->json([
									'success' => false,
									'errors' => ['Lodge sharing employee details is required'],
								]);
							}
							foreach ($lodge_share_details as $share_detail) {
								$lodge_share_detail = LodgingShareDetail::firstOrNew([
									'lodging_id' => $lodging->id,
									'employee_id' => $share_detail['employee_id'],
								]);
								if (!$lodge_share_detail->exists) {
									$lodge_share_detail->created_at = Carbon::now();
								} else {
									$lodge_share_detail->updated_at = Carbon::now();
								}
								$lodge_share_detail->save();
							}
						}

						//STORE ATTACHMENT
						// $item_images = storage_path('app/public/trip/lodgings/attachments/');
						// Storage::makeDirectory($item_images, 0777);
						// if (!empty($lodging_data['attachments'])) {
						// 	foreach ($lodging_data['attachments'] as $key => $attachement) {
						// 		$name = $attachement->getClientOriginalName();
						// 		$attachement->move(storage_path('app/public/trip/lodgings/attachments/'), $name);
						// 		$attachement_lodge = new Attachment;
						// 		$attachement_lodge->attachment_of_id = 3181;
						// 		$attachement_lodge->attachment_type_id = 3200;
						// 		$attachement_lodge->entity_id = $lodging->id;
						// 		$attachement_lodge->name = $name;
						// 		$attachement_lodge->save();
						// 	}
						// }
						// dump($lodging_data);
					}
					// dd();
					// dd('1');
					//SAVE LODGING ATTACHMENT
					$item_images = storage_path('app/public/trip/lodgings/attachments/');
					Storage::makeDirectory($item_images, 0777);
					if (!empty($request->lodging_attachments)) {
						foreach ($request->lodging_attachments as $key => $attachement) {
							$value = rand(1, 100);
							$image = $attachement;
							$extension = $image->getClientOriginalExtension();
							$name = $request->trip_id . '_lodging_attachment' . $value . '.' . $extension;
							$attachement->move(storage_path('app/public/trip/lodgings/attachments/'), $name);
							$attachement_lodge = new Attachment;
							$attachement_lodge->attachment_of_id = 3181;
							$attachement_lodge->attachment_type_id = 3200;
							$attachement_lodge->entity_id = $request->trip_id;
							$attachement_lodge->name = $name;
							$attachement_lodge->save();
						}
						$loding_attachment_exist = true;
					}

					//SAVE EMPLOYEE CLAIMS
					$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
					$employee_claim->lodging_total = $lodging_total_amount;
					$employee_claim->employee_id = Auth::user()->entity_id;
					$employee_claim->status_id = 3033; //CLAIM INPROGRESS
					$employee_claim->created_by = Auth::user()->id;

					$employee_claim->save();
				} else {
					$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
					$employee_claim->lodging_total = 0;
					$employee_claim->save();
				}

				$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
				$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
				$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
				$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
				$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;

				//Check Beta Amount
				if ($lodging_amount == 0 && $boarding_amount == 0) {
					$employee_beta_amount = $beta_amount * $employee_claim->total_trip_days;
					$total_amount += $employee_beta_amount;

					$employee_claim->beta_amount = $employee_beta_amount;
				} else {
					$employee_claim->beta_amount = NULL;
				}

				// $employee_claim->total_trip_days = $request->trip_total_days;
				$employee_claim->total_amount = $total_amount;

				//To Find Amount to Pay Financier or Employee
				if ($trip->advance_received) {
					if ($trip->advance_received > $total_amount) {
						$balance_amount = $trip->advance_received - $total_amount;
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 2;
					} else {
						$balance_amount = $total_amount - $trip->advance_received;
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 1;
					}
				} else {
					$employee_claim->balance_amount = $total_amount ? $total_amount : 0;
					$employee_claim->amount_to_pay = 1;
				}

				$employee_claim->save();

				//GET SAVED LODGINGS
				$saved_lodgings = Trip::with([
					'lodgings',
					'lodgings.lodgingTaxInvoice',
					'lodgings.drywashTaxInvoice',
					'lodgings.boardingTaxInvoice',
					'lodgings.othersTaxInvoice',
					'lodgings.discountTaxInvoice',
					'lodgings.roundoffTaxInvoice',
					'lodging_attachments',
					'lodgings.city',
					'lodgings.stateType',
					'lodgings.attachments',
					'lodgings.shareDetails',
				])->find($request->trip_id);

				//LODGE SHARE DETAILS
				if (count($saved_lodgings->lodgings) > 0) {
					foreach ($saved_lodgings->lodgings as $lodge_data) {
						$lodge_share_data = [];
						foreach ($lodge_data->shareDetails as $share_key => $share_data) {
							$lodge_share_data[$share_key] = LodgingShareDetail::select([
								'lodging_share_details.id',
								'employees.id as employee_id',
								'employees.code as employee_code',
								'employees.grade_id',
								'outlets.code as outlet_code',
								'outlets.name as outlet_name',
								'users.name as user_name',
								'grades.name as grade',
								'designations.name as designation',
								'sbus.name as sbu',
							])
								->join('employees', 'employees.id', 'lodging_share_details.employee_id')
								->join('outlets', 'outlets.id', 'employees.outlet_id')
								->join('entities as grades', 'grades.id', 'employees.grade_id')
								->leftjoin('designations', 'designations.id', 'employees.designation_id')
								->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
								->join('users', 'users.entity_id', 'employees.id')
								->where('users.user_type_id', 3121) //EMPLOYEE
								->where('lodging_share_details.id', $share_data->id)
								->first();
							$lodge_share_data[$share_key]->eligible_amount = 0.00;

							$lodge_city_category_id = NCity::where('id', $lodge_data->city_id)
								->pluck('category_id')
								->first();
							// $lodge_share_data[$share_key]['normal'] = [
							// 	'eligible_amount' => 0,
							// ];

							if ($lodge_city_category_id) {
								$lodge_expense_config = DB::table('grade_expense_type')
									->where('grade_id', $lodge_share_data[$share_key]->grade_id)
									->where('expense_type_id', 3001) //LODGING EXPENSES
									->where('city_category_id', $lodge_city_category_id)
									->first();
								if (!empty($lodge_expense_config)) {
									// $lodge_share_data[$share_key]['normal'] = [
									// 	'eligible_amount' => $lodge_expense_config->eligible_amount,
									// ];
									$lodge_share_data[$share_key]->eligible_amount = $lodge_expense_config->eligible_amount;
								}
							}
						}
						$lodge_data['sharing_employees'] = $lodge_share_data;
					}
				}

				// dd($saved_lodgings);
				//BOARDING CITIES LIST ==> NOT BEEN USED NOW

				// $boarding_dates_list = array();
				// $travel_dates = Visit::select(DB::raw('MAX(DATE_FORMAT(visits.arrival_date,"%d-%m-%Y")) as max_date'), DB::raw('MIN(DATE_FORMAT(visits.departure_date,"%d-%m-%Y")) as min_date'))->where('visits.trip_id', $request->trip_id)->first();

				// if ($travel_dates) {
				// 	$boarding_date_range = Trip::getDatesFromRange($travel_dates->min_date, $travel_dates->max_date);
				// 	if (!empty($boarding_date_range)) {
				// 		$boarding_dates_list[0]['id'] = '';
				// 		$boarding_dates_list[0]['name'] = 'Select Date';
				// 		foreach ($boarding_date_range as $boarding_date_range_key => $boarding_date_range_val) {
				// 			$boarding_date_range_key++;
				// 			$boarding_dates_list[$boarding_date_range_key]['id'] = $boarding_date_range_val;
				// 			$boarding_dates_list[$boarding_date_range_key]['name'] = $boarding_date_range_val;
				// 		}
				// 	}
				// } else {
				// 	$boarding_dates_list = array();
				// }
				$trip->status_id = 3033; //CLAIM INPROGRESS
				$trip->save();

				DB::commit();
				return response()->json(['success' => true, 'saved_lodgings' => $saved_lodgings]);
			}

			//SAVING BOARDINGS
			if ($request->is_boarding) {
				//REMOVE BOARDINGS ATTACHMENT
				if (!empty($request->boardings_attach_removal_ids)) {
					$boardings_attach_removal_ids = json_decode($request->boardings_attach_removal_ids, true);
					Attachment::whereIn('id', $boardings_attach_removal_ids)->delete();
				}
				//REMOVE BOARDINGS
				if (!empty($request->boardings_removal_id)) {
					$boardings_removal_id = json_decode($request->boardings_removal_id, true);
					Boarding::whereIn('id', $boardings_removal_id)->delete();
					// Attachment::whereIn('entity_id', $boardings_removal_id)->delete();
				}

				//SAVE
				if ($request->boardings) {
					//TOTAL BOARDING DAYS SHOULD NOT EXCEED TOTAL TRIP DAYS
					$boarding_days = (int) array_sum(array_column($request->boardings, 'days'));
					$trip_total_days = (int) $request->trip_total_days;
					if ($boarding_days > $trip_total_days + 1) {
						//need to verify
						return response()->json(['success' => false, 'errors' => ['Total boarding days should be less than total trip days']]);
					}

					//Delete previous entries
					$boardings = Boarding::where('trip_id', $request->trip_id)->forceDelete();

					$boarding_total_amount = 0;
					foreach ($request->boardings as $boarding_data) {

						if (isset($boarding_data['id'])) {
							$boarding = Boarding::where('id', $boarding_data['id'])->first();
							if (!$boarding) {
								$boarding = new Boarding;
							}
						} else {
							$boarding = new Boarding;
						}

						$boarding->fill($boarding_data);
						//dd($boarding_data);
						$boarding->trip_id = $request->trip_id;
						$boarding->from_date = date('Y-m-d', strtotime($boarding_data['from_date']));
						$boarding->to_date = date('Y-m-d', strtotime($boarding_data['to_date']));
						$boarding->created_by = Auth::user()->id;
						$boarding->save();

						$boarding_total = 0;
						if ($boarding) {
							$boarding_total = $boarding->amount + $boarding->cgst + $boarding->sgst + $boarding->igst;
							$boarding_total_amount += $boarding_total;
						}

						//STORE ATTACHMENT
						// $item_images = storage_path('app/public/trip/boarding/attachments/');
						// Storage::makeDirectory($item_images, 0777);
						// if (!empty($boarding_data['attachments'])) {
						// 	foreach ($boarding_data['attachments'] as $key => $attachement) {
						// 		$name = $attachement->getClientOriginalName();
						// 		$attachement->move(storage_path('app/public/trip/boarding/attachments/'), $name);
						// 		$attachement_board = new Attachment;
						// 		$attachement_board->attachment_of_id = 3182;
						// 		$attachement_board->attachment_type_id = 3200;
						// 		$attachement_board->entity_id = $boarding->id;
						// 		$attachement_board->name = $name;
						// 		$attachement_board->save();
						// 	}
						// }
					}
					// dd($boarding_total_amount);
					//SAVE BOARDING ATTACHMENT
					$item_images = storage_path('app/public/trip/boarding/attachments/');
					Storage::makeDirectory($item_images, 0777);
					if (!empty($request->boarding_attachments)) {
						foreach ($request->boarding_attachments as $key => $attachement) {
							$value = rand(1, 100);
							$image = $attachement;
							$extension = $image->getClientOriginalExtension();
							$name = $request->trip_id . '_boarding_attachment' . $value . '.' . $extension;
							$attachement->move(storage_path('app/public/trip/boarding/attachments/'), $name);
							$attachement_lodge = new Attachment;
							$attachement_lodge->attachment_of_id = 3182;
							$attachement_lodge->attachment_type_id = 3200;
							$attachement_lodge->entity_id = $request->trip_id;
							$attachement_lodge->name = $name;
							$attachement_lodge->save();
						}
					}

					//SAVE EMPLOYEE CLAIMS
					$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
					$employee_claim->boarding_total = $boarding_total_amount;
					$employee_claim->employee_id = Auth::user()->entity_id;
					$employee_claim->status_id = 3033; //CLAIM INPROGRESS
					$employee_claim->created_by = Auth::user()->id;
					$employee_claim->save();
				} else {
					$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
					$employee_claim->boarding_total = 0;
					$employee_claim->save();
				}

				$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
				$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
				$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
				$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
				$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;

				//Check Beta Amount
				if ($lodging_amount == 0 && $boarding_amount == 0) {
					$employee_beta_amount = $beta_amount * $employee_claim->total_trip_days;
					$total_amount += $employee_beta_amount;

					$employee_claim->beta_amount = $employee_beta_amount;
				} else {
					$employee_claim->beta_amount = NULL;
				}

				// $employee_claim->total_trip_days = $request->trip_total_days;
				$employee_claim->total_amount = $total_amount;

				//To Find Amount to Pay Financier or Employee
				if ($trip->advance_received) {
					if ($trip->advance_received > $total_amount) {
						$balance_amount = $trip->advance_received - $total_amount;
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 2;
					} else {
						$balance_amount = $total_amount - $trip->advance_received;
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 1;
					}
				} else {
					$employee_claim->balance_amount = $total_amount ? $total_amount : 0;
					$employee_claim->amount_to_pay = 1;
				}

				$employee_claim->save();

				$trip->status_id = 3033; //CLAIM INPROGRESS
				$trip->save();
				//GET SAVED BOARDINGS
				$saved_boardings = Trip::with([
					'boardings',
					'boardings.stateType',
					'boarding_attachments',
					'boardings.city',
					'boardings.attachments',
				])->find($request->trip_id);
				DB::commit();
				return response()->json(['success' => true, 'saved_boardings' => $saved_boardings]);
			}

			//SAVE LOCAL TRAVELS
			if ($request->is_local_travel) {
				//GET EMPLOYEE DETAILS
				$employee = Employee::where('id', $request->employee_id)->first();
				$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
				//UPDATE TRIP STATUS
				$trip = Trip::find($request->trip_id);
				$trip->rejection_remarks = NULL;
				$trip->save();

				$trip->status_id = 3033; //Claim requested
				$employee_claim->status_id = 3033; //CLAIM REQUESTED

				$trip->claim_amount = $request->claim_total_amount; //claimed
				$trip->claimed_date = date('Y-m-d H:i:s');
				$trip->rejection_id = NULL;
				$trip->rejection_remarks = NULL;
				$trip->save();

				//SAVE LOCAL TRAVEL ATTACHMENT
				$item_images = storage_path('app/public/trip/local_travel/attachments/');
				Storage::makeDirectory($item_images, 0777);
				if (!empty($request->local_travel_attachments)) {
					foreach ($request->local_travel_attachments as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $request->trip_id . '_local_travel_attachment' . $value . '.' . $extension;
						$attachement->move(storage_path('app/public/trip/local_travel/attachments/'), $name);
						$attachement_local_travel = new Attachment;
						$attachement_local_travel->attachment_of_id = 3183;
						$attachement_local_travel->attachment_type_id = 3200;
						$attachement_local_travel->entity_id = $request->trip_id;
						$attachement_local_travel->name = $name;
						$attachement_local_travel->save();
					}
				}
				//CHECK IS JUSTIFY MY TRIP CHECKBOX CHECKED OR NOT
				// if ($request->is_justify_my_trip) {
				// 	$employee_claim->is_justify_my_trip = 1;
				// } else {
				// 	$employee_claim->is_justify_my_trip = 0;
				// }

				//CHECK EMPLOYEE GRADE HAS DEVIATION ELIGIBILITY ==> IF DEVIATION ELIGIBILITY IS 2-NO MEANS THERE IS NO DEVIATION, 1-YES MEANS NEED TO CHECK IN REQUEST
				// $grade_advance_eligibility = GradeAdvancedEligiblity::where('grade_id', $request->grade_id)->first();
				// if ($grade_advance_eligibility && $grade_advance_eligibility->deviation_eligiblity == 2) {
				// 	$employee_claim->is_deviation = 0; //NO DEVIATION DEFAULT
				// } else {
				// 	$employee_claim->is_deviation = $request->is_deviation;
				// }
				// Changed deviation by Karthick T on 21-01-2022
				// If lodging exist and attachment not found the claim will go to deviation
				// $lodge_attachment_count = Attachment::where('attachment_of_id', 3181)
				// 	->where('attachment_type_id', 3200)
				// 	->where('entity_id', $request->trip_id)
				// 	->count();
				// $lodging_count = Lodging::where('trip_id', $request->trip_id)->count();
				// if ($lodging_count > 0 && $lodge_attachment_count == 0) {
				// 	$employee_claim->is_deviation = 1;
				// }

				// if (isset($loding_attachment_exist) && $loding_attachment_exist == false && $employee_claim->is_deviation == 0)
				// Changed deviation by Karthick T on 21-01-2022

				// $employee_claim->sbu_id = null;
				// if (isset($request->sbu_id) && $request->sbu_id)
				// 	$employee_claim->sbu_id = $request->sbu_id;
				$employee_claim->created_by = Auth::user()->id;
				$employee_claim->remarks = $request->remarks;
				$employee_claim->save();

				//STORE GOOGLE ATTACHMENT
				$item_images = storage_path('app/public/trip/ey_employee_claims/google_attachments/');
				Storage::makeDirectory($item_images, 0777);
				if ($request->hasfile('google_attachments')) {

					foreach ($request->file('google_attachments') as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $trip->id . 'google_attachment' . $value . '.' . $extension;
						$image->move(storage_path('app/public/trip/ey_employee_claims/google_attachments/'), $name);
						$attachement = new Attachment;
						$attachement->attachment_of_id = 3185;
						$attachement->attachment_type_id = 3200;
						$attachement->entity_id = $trip->id;
						$attachement->name = $name;
						$attachement->save();
					}

				}

				// $activity['entity_id'] = $trip->id;
				// $activity['entity_type'] = "Trip";
				// $activity['details'] = "Trip is ";
				// $activity['activity'] = "claim";
				// $activity_log = ActivityLog::saveLog($activity);

				if (!empty($request->local_travels_removal_id)) {
					$local_travels_removal_id = json_decode($request->local_travels_removal_id, true);
					LocalTravel::whereIn('id', $local_travels_removal_id)->delete();
				}
				if ($request->local_travels) {

					//Delete previous entries
					$local_travels = LocalTravel::where('trip_id', $request->trip_id)->forceDelete();

					$local_total_amount = 0;
					foreach ($request->local_travels as $local_travel_data) {
						if (isset($local_travel_data['id'])) {
							$local_travel = LocalTravel::where('id', $local_travel_data['id'])->first();
							if (!$local_travel) {
								$local_travel = new LocalTravel;
							}
						} else {
							$local_travel = new LocalTravel;
						}

						$local_travel->fill($local_travel_data);
						$local_travel->trip_id = $request->trip_id;
						$local_travel->date = date('Y-m-d', strtotime($local_travel_data['date']));
						$local_travel->created_by = Auth::user()->id;
						$local_travel->save();

						$local_amount_total = 0;
						if ($local_travel) {
							$local_amount_total = $local_travel->amount + $local_travel->cgst + $local_travel->sgst + $local_travel->igst;
							$local_total_amount += $local_amount_total;
						}

						// //STORE ATTACHMENT
						// $item_images = storage_path('app/public/trip/local_travels/attachments/');
						// Storage::makeDirectory($item_images, 0777);
						// if (!empty($local_travel_data['attachments'])) {
						// 	foreach ($local_travel_data['attachments'] as $key => $attachement) {
						// 		$name = $attachement->getClientOriginalName();
						// 		$attachement->move(storage_path('app/public/trip/local_travels/attachments/'), $name);
						// 		$attachement_local_travel = new Attachment;
						// 		$attachement_local_travel->attachment_of_id = 3183;
						// 		$attachement_local_travel->attachment_type_id = 3200;
						// 		$attachement_local_travel->entity_id = $local_travel->id;
						// 		$attachement_local_travel->name = $name;
						// 		$attachement_local_travel->save();
						// 	}
						// }
					}
					$employee_claim->local_travel_total = $local_total_amount;
					$employee_claim->save();
				} else {
					$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
					$employee_claim->local_travel_total = 0;
					$employee_claim->save();
				}

				// $transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
				// $lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
				// $boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
				// $local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
				// $total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;

				//Check Beta Amount
				// if ($lodging_amount == 0 && $boarding_amount == 0) {
				// 	$employee_beta_amount = $beta_amount * $employee_claim->total_trip_days;
				// 	$total_amount += $employee_beta_amount;

				// 	$employee_claim->beta_amount = $employee_beta_amount;
				// } else {
				// 	$employee_claim->beta_amount = NULL;
				// }

				// $employee_claim->total_trip_days = $request->trip_total_days;
				// $employee_claim->total_amount = $total_amount;

				//To Find Amount to Pay Financier or Employee
				// if ($trip->advance_received) {
				// 	if ($trip->advance_received > $total_amount) {
				// 		$balance_amount = $trip->advance_received - $total_amount;
				// 		$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
				// 		$employee_claim->amount_to_pay = 2;
				// 	} else {
				// 		$employee_claim->amount_to_pay = 1;
				// 	}
				// } else {
				// 	$employee_claim->amount_to_pay = 1;
				// }

				$employee_claim->save();

				// $employee = Employee::where('id', $trip->employee_id)->first();
				// $user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();
				// $notification = sendnotification($type = 5, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Claim Requested');

				// DB::commit();
				// return response()->json(['success' => true]);
				$trip->status_id = 3033; //CLAIM INPROGRESS
				$trip->save();
				//GET SAVED TRAVELS
				$local_travels = Trip::with([
					'localTravels',
					'localTravels.city',
				])->find($request->trip_id);
				DB::commit();
				return response()->json(['success' => true, 'local_travels' => $local_travels]);
			}
			//FINAL SAVE
			if ($request->is_attachment_trip) {
				//GET EMPLOYEE DETAILS
				$employee = Employee::where('id', $request->employee_id)->first();
				$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
				//UPDATE TRIP STATUS
				$trip = Trip::find($request->trip_id);
				$trip->rejection_remarks = NULL;
				$trip->save();

				//CHECK IF EMPLOYEE SELF APPROVE
				if ($employee->self_approve == 1) {
					$trip->status_id = 3034; // Payment Pending
					$employee_claim->status_id = 3034; //PAYMENT PENDING
				} else {
					$trip->status_id = 3023; //Claim requested
					$employee_claim->status_id = 3023; //CLAIM REQUESTED
				}
				// Deviation reason by Karthick T on 04-08-2022
				$employee_claim->deviation_reason = (isset($request->deviation_reason) && $request->deviation_reason) ? $request->deviation_reason : '';
				//dd($request->claim_total_amount);
				$trip->claim_amount = $request->claim_total_amount; //claimed
				$trip->claimed_date = date('Y-m-d H:i:s');
				$trip->rejection_id = NULL;
				$trip->rejection_remarks = NULL;
				$trip->save();

				//CHECK IS JUSTIFY MY TRIP CHECKBOX CHECKED OR NOT
				if ($request->is_justify_my_trip) {
					$employee_claim->is_justify_my_trip = 1;
				} else {
					$employee_claim->is_justify_my_trip = 0;
				}

				//CHECK EMPLOYEE GRADE HAS DEVIATION ELIGIBILITY ==> IF DEVIATION ELIGIBILITY IS 2-NO MEANS THERE IS NO DEVIATION, 1-YES MEANS NEED TO CHECK IN REQUEST
				$grade_advance_eligibility = GradeAdvancedEligiblity::where('grade_id', $request->grade_id)->first();
				if ($grade_advance_eligibility && $grade_advance_eligibility->deviation_eligiblity == 2) {
					$employee_claim->is_deviation = 0; //NO DEVIATION DEFAULT
				} else {
					$employee_claim->is_deviation = $request->is_deviation;
				}
				// $grade_travel_mode_eligibility = DB::table('grade_travel_mode')->where('travel_mode_id', $request->travel_mode_id)->first();
				// if ($grade_travel_mode_eligibility && $grade_travel_mode_eligibility->deviation_eligiblity == 2) {
				// 	$employee_claim->is_deviation = 0; //NO DEVIATION DEFAULT
				// } else {
				// 	// $employee_claim->is_deviation = 1;
				// 	if($is_grade_leader == false){
				// 		$employee_claim->is_deviation = 1;
				// 	}
				// }

				// If transport exist and attachment not found the claim will go to deviation
				$transport_attachment_count = Attachment::where('attachment_of_id', 3751)
					->where('attachment_type_id', 3200)
					->where('entity_id', $request->trip_id)
					->count();
				$self_booking_count = Visit::where('booking_method_id', 3040)->where('trip_id', $request->trip_id)->select('id', 'booking_method_id')->get();
				if (!empty($self_booking_count->booking_method_id) == 3040) {
					foreach ($self_booking_count as $key => $value) {
						$transport_count = VisitBooking::where('visit_id', $value->id)->count();
					}
					// if ($transport_count > 0 && $transport_attachment_count == 0) {
					if ($transport_count > 0 && $transport_attachment_count == 0 && $is_grade_leader == false) {
						// $employee_claim->is_deviation = 1;
					}
				}

				// Changed deviation by Karthick T on 21-01-2022
				// If lodging exist and attachment not found the claim will go to deviation
				$lodge_attachment_count = Attachment::where('attachment_of_id', 3752)
					->where('attachment_type_id', 3200)
					->where('entity_id', $request->trip_id)
					->count();
				$lodging_count = Lodging::where('trip_id', $request->trip_id)->count();
				// if ($lodging_count > 0 && $lodge_attachment_count == 0) {
				if ($lodging_count > 0 && $lodge_attachment_count == 0 && $is_grade_leader == false) {
					$employee_claim->is_deviation = 1;
				}
				// if($grade_advance_eligibility->deviation_eligiblity == 1){
				if ($grade_advance_eligibility->deviation_eligiblity == 1 && $is_grade_leader == false) {
					if ($employee_claim->deviation_reason == NULL) {
						$employee_claim->is_deviation = 0; //NO DEVIATION DEFAULT
					} else {
						$employee_claim->is_deviation = 1;
					}
				}
				// if (isset($loding_attachment_exist) && $loding_attachment_exist == false && $employee_claim->is_deviation == 0)
				// Changed deviation by Karthick T on 21-01-2022

				$employee_claim->sbu_id = null;
				if (isset($request->sbu_id) && $request->sbu_id) {
					$employee_claim->sbu_id = $request->sbu_id;
				}

				$employee_claim->created_by = Auth::user()->id;
				$employee_claim->remarks = $request->remarks;
				$employee_claim->save();

				//STORE GOOGLE ATTACHMENT
				$item_images = storage_path('app/public/trip/ey_employee_claims/google_attachments/');
				Storage::makeDirectory($item_images, 0777);
				if ($request->hasfile('google_attachments')) {
					$validator = Validator::make($request->all(), [
                        'google_attachments.*' => [
                            'mimes:jpeg,jpg,pdf,png',
                        ],
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => ['The attachement must be a jpeg, jpg, pdf, or png file.'],
                        ]);
                    }

					foreach ($request->file('google_attachments') as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $trip->id . 'google_attachment' . $value . '.' . $extension;
						$image->move(storage_path('app/public/trip/ey_employee_claims/google_attachments/'), $name);
						$attachement = new Attachment;
						$attachement->attachment_of_id = 3185;
						$attachement->attachment_type_id = 3200;
						$attachement->entity_id = $trip->id;
						$attachement->name = $name;
						$attachement->save();
					}

				}

				$activity['entity_id'] = $trip->id;
				$activity['entity_type'] = "Trip";
				$activity['details'] = "Trip is Claimed";
				$activity['activity'] = "claim";
				$activity_log = ActivityLog::saveLog($activity);

				$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
				$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
				$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
				$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
				$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;

				//Check Beta Amount
				if ($lodging_amount == 0 && $boarding_amount == 0) {
					$employee_beta_amount = $beta_amount * $employee_claim->total_trip_days;
					$total_amount += $employee_beta_amount;

					$employee_claim->beta_amount = $employee_beta_amount;
				} else {
					$employee_claim->beta_amount = NULL;
				}

				// $employee_claim->total_trip_days = $request->trip_total_days;
				$employee_claim->total_amount = $total_amount;

				//To Find Amount to Pay Financier or Employee
				if ($trip->advance_received) {
					if ($trip->advance_received > $total_amount) {
						$balance_amount = round($trip->advance_received - $total_amount);
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 2;
					} else {
						$balance_amount = round($total_amount - $trip->advance_received);
						$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
						$employee_claim->amount_to_pay = 1;
					}
				} else {
					$employee_claim->balance_amount = round($total_amount) ? round($total_amount) : 0;
					$employee_claim->amount_to_pay = 1;
				}

				if(isset($request->employee_return_payment_mode_id)){
					$employee_claim->employee_return_payment_mode_id = $request->employee_return_payment_mode_id;
				}
				$employee_claim->save();

				$employee = Employee::where('id', $trip->employee_id)->first();
				$user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();
				$notification = sendnotification($type = 5, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Claim Requested');

				DB::commit();
				return response()->json(['success' => true]);
			}

			$request->session()->flash('success', 'Trip saved successfully!');
			return response()->json(['success' => true]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	//GET TRAVEL MODE CATEGORY STATUS TO CHECK IF IT IS NO VEHICLE CLAIM
	public static function getVisitTrnasportModeClaimStatus($request) {
		// if (!empty($request->travel_mode_id)) {
		// 	$travel_mode_category_type = DB::table('travel_mode_category_type')->where('travel_mode_id', $request->travel_mode_id)->where('category_id', 3402)->first();
		// 	if ($travel_mode_category_type) {
		// 		$is_no_vehicl_claim = true;
		// 	} else {
		// 		$is_no_vehicl_claim = false;
		// 	}
		// } else {
		// 	$is_no_vehicl_claim = false;
		// }
		// return response()->json(['is_no_vehicl_claim' => $is_no_vehicl_claim]);
		if (!empty($request->travel_mode_id)) {
			$travel_mode_category_type = DB::table('travel_mode_category_type')->where('travel_mode_id', $request->travel_mode_id)->pluck('category_id')->first();
			if ($travel_mode_category_type) {
				$category_type = $travel_mode_category_type;
			} else {
				$category_type = false;
			}
		} else {
			$category_type = false;
		}
		return response()->json(['category_type' => $category_type]);
	}

	// Checking attachment status by Karthick T on 20-01-2022
	// Checking all the attachments are viewed or not
	public static function validateAttachment($trip_id) {
		$trip_attachment = Trip::with([
			// 'visits.pending_attachments',
			// 'lodgings.pending_attachments',
			// 'boardings.pending_attachments',
			// 'pending_transport_attachments',
			// 'pending_lodging_attachments',
			// 'pending_boarding_attachments',
			// 'pending_local_travel_attachments',
			'pendingTripAttachments',
			'pending_google_attachments',
		])->find($trip_id);
		$pending_count = 0;
		if ($trip_attachment) {
			// foreach($trip_attachment->visits as $visit) {
			// 	$pending_count += count($visit->pending_attachments);
			// }
			// foreach ($trip_attachment->lodgings as $lodging) {
			// 	$pending_count += count($lodging->pending_attachments);
			// }
			// foreach ($trip_attachment->boardings as $boarding) {
			// 	$pending_count += count($boarding->pending_attachments);
			// }
			// $pending_count += count($trip_attachment->pending_transport_attachments);
			// $pending_count += count($trip_attachment->pending_lodging_attachments);
			// $pending_count += count($trip_attachment->pending_boarding_attachments);
			// $pending_count += count($trip_attachment->pending_local_travel_attachments);
			$pending_count += count($trip_attachment->pending_google_attachments);
			$pending_count += count($trip_attachment->pendingTripAttachments);
		}
		$approval_status = ($pending_count == 0) ? false : true;
		return $approval_status;
	}
	// Checking attachment status by Karthick T on 20-01-2022
	// Pending outstation trip mail by Karthick T on 15-02-2022
	public static function pendingTripMail($date, $status, $title) {
		$pending_trips = [];
		if ($status == 'Pending Requsation Approval') {
			$pending_trips = Trip::select(
				'trips.number',
				'trips.employee_id',
				'users.name as employee_name',
				DB::raw('DATE_FORMAT(trips.created_at,"%d/%m/%Y") as created_at'),
				DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
				'fromcity.name as fromcity_name',
				'tocity.name as tocity_name',
				DB::raw('DATE_FORMAT(trips.created_at,"%Y-%m-%d") as trip_date')
			)->leftjoin('users', 'trips.employee_id', 'users.entity_id')
				->join('visits', 'visits.trip_id', 'trips.id')
				->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
				->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
			// ->whereDate('trips.created_at', $date)
			// // ->whereDate('trips.end_date', $date)
			// ->where('trips.status_id', '=', 3021)
				->where(function ($q) use ($date, $title) {
					$q->where('trips.status_id', '=', 3021);
					if ($title == 'Cancelled') {
						$q->whereDate('trips.created_at', '<=', $date);
					} else {
						$q->whereDate('trips.created_at', $date);
					}
				})
				->groupBy('trips.id')
				->get();
		} elseif ($status == 'Claim Generation') {
			$pending_trips = Trip::select(
				'trips.number',
				'trips.employee_id',
				'users.name as employee_name',
				DB::raw('DATE_FORMAT(trips.end_date,"%d/%m/%Y") as end_date'),
				DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
				'fromcity.name as fromcity_name',
				'tocity.name as tocity_name',
				DB::raw('DATE_FORMAT(trips.end_date,"%Y-%m-%d") as trip_date')
			)->leftjoin('users', 'trips.employee_id', 'users.entity_id')
				->join('visits', 'visits.trip_id', 'trips.id')
				->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
				->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
				->leftJoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id')
			// ->where('trips.end_date', $date)
			// ->whereNull('ey_employee_claims.number')
			// ->where('trips.status_id', '=', 3028)
				->where(function ($q) use ($date, $title) {
					$q->where('trips.status_id', '=', 3028)
						->whereNull('ey_employee_claims.number');
					if ($title == 'Cancelled') {
						$q->whereDate('trips.end_date', '<=', $date);
					} else {
						$q->whereDate('trips.end_date', $date);
					}
				})
				->groupBy('trips.id')
				->get();
		} elseif ($status == 'Pending Claim Approval') {
			$pending_trips = Trip::select(
				'trips.number',
				'trips.employee_id',
				'users.name as employee_name',
				DB::raw('DATE_FORMAT(ey_employee_claims.created_at,"%d/%m/%Y") as created_at'),
				DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
				'fromcity.name as fromcity_name',
				'tocity.name as tocity_name',
				DB::raw('DATE_FORMAT(trips.end_date,"%Y-%m-%d") as trip_date')
			)->leftjoin('users', 'trips.employee_id', 'users.entity_id')
				->join('visits', 'visits.trip_id', 'trips.id')
				->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
				->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
				->leftJoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id')
			// // ->whereDate('ey_employee_claims.created_at', $date)
			// ->whereDate('trips.end_date', $date)
			// ->where('trips.status_id', '=', 3023) //Claim Requested
				->where(function ($q) use ($date, $title) {
					$q->where('trips.status_id', '=', 3023); //Claim Requested
					if ($title == 'Cancelled') {
						$q->whereDate('trips.end_date', '<=', $date);
					} else {
						$q->whereDate('trips.end_date', $date);
					}
				})
				->groupBy('trips.id')
				->get();

		} elseif ($status == 'Pending Divation Claim Approval') {
			$pending_trips = Trip::select(
				'trips.number',
				'trips.employee_id',
				'users.name as employee_name',
				DB::raw('DATE_FORMAT(ey_employee_claims.created_at,"%d/%m/%Y") as created_at'),
				DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
				'fromcity.name as fromcity_name',
				'tocity.name as tocity_name',
				DB::raw('DATE_FORMAT(trips.end_date,"%Y-%m-%d") as trip_date')
			)->leftjoin('users', 'trips.employee_id', 'users.entity_id')
				->join('visits', 'visits.trip_id', 'trips.id')
				->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
				->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
				->leftJoin('ey_employee_claims', 'ey_employee_claims.trip_id', 'trips.id')
			// ->whereDate('ey_employee_claims.created_at', $date)
			// ->whereDate('trips.end_date', $date)
			// ->where('trips.status_id', '=', 3029) //Senior Manager Approval Pending
				->where(function ($q) use ($date, $title) {
					$q->where('trips.status_id', '=', 3029); //Senior Manager Approval Pending
					if ($title == 'Cancelled') {
						$q->whereDate('trips.end_date', '<=', $date);
					} else {
						$q->whereDate('trips.end_date', $date);
					}
				})
				->groupBy('trips.id')
				->get();
		}
		if (count($pending_trips) > 0) {
			foreach ($pending_trips as $trip_key => $pending_trip) {
				$sendSmsAndMail = $pending_trip->trip_date == $date;
				if ($status == 'Pending Requsation Approval') {
					$detail = 'You have the following Travel Requisition(s) waiting for Approval and is / are
pending for more than 2 days. Please approve. In case any of the below travel
request is not desired, then those may be rejected.';
					$content = 'Trip Number -' . $pending_trip->number . ',' . 'Employee Name -' . $pending_trip->employee_name . ',' . 'Trip date -' . $pending_trip->visit_date . ',' . 'Trip From City  -' . $pending_trip->fromcity_name . ',' . 'Trip To City  -' . $pending_trip->tocity_name;
					$subject = 'Pending Trip Approval Mail';
					$arr['detail'] = $detail;
					$arr['content'] = $content;
					$arr['subject'] = $subject;
					$to_email = $arr['to_email'] = Employee::select('employees.id', 'users.email as email', 'users.name as name', 'users.mobile_number')
						->join('users', 'users.entity_id', 'employees.reporting_to_id')
						->where('users.user_type_id', 3121)
						->where('employees.id', $pending_trip->employee_id)
						->get()->toArray();
					//dd($to_email[0]['mobile_number']);
					foreach ($to_email as $key => $value) {
						$mobile_number = $value['mobile_number'];
						$employee_id = $value['id'];
					}
					//dd($mobile_number);
					if ($title == 'Remainder') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3021)
							->update(['reason' => 'Remainder ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_REQUEST_REMINDER'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 2, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Warning') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3021)
							->update(['reason' => 'Warning ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_REQUEST_WARNING'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 8, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Cancelled') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)->where('status_id', 3021)->update(['status_id' => 3038, 'reason' => 'Your Trip not approved,So system Cancelled Automatically', 'updated_at' => Carbon::now()]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_REQUEST_CANCELL'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 10, $message);
						if ($mobile_number && $sendSmsAndMail) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
				} elseif ($status == 'Claim Generation') {
					$detail = 'You have the following Travel Requisition(s) waiting for claim generation and is / are
pending for more than 2 days. Please claim your trip. In case any of the below travel
request is not desired, then those may be cancelled.';
					$content = 'Trip Number -' . $pending_trip->number . ',' . 'Employee Name -' . $pending_trip->employee_name . ',' . 'Trip date -' . $pending_trip->visit_date . ',' . 'Trip From City  -' . $pending_trip->fromcity_name . ',' . 'Trip To City  -' . $pending_trip->tocity_name;
					$subject = 'Pending Claim Generation Mail';
					$arr['detail'] = $detail;
					$arr['content'] = $content;
					$arr['subject'] = $subject;
					$to_email = $arr['to_email'] = Employee::select('employees.id', 'users.email as email', 'users.name as name', 'users.mobile_number')
						->join('users', 'users.entity_id', 'employees.id')
						->where('users.user_type_id', 3121)
						->where('employees.id', $pending_trip->employee_id)
						->get()->toArray();
					foreach ($to_email as $key => $value) {
						$mobile_number = $value['mobile_number'];
						$employee_id = $value['id'];
					}
					if ($title == 'Remainder') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3028)
							->update(['reason' => 'Remainder ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_GENERATION'));
						$message = str_replace('YYYY', $pending_trip->end_date, $message);
						$message = str_replace('ZZZ', 2, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Warning') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3028)
							->update(['reason' => 'Warning ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_GENERATION'));
						$message = str_replace('YYYY', $pending_trip->end_date, $message);
						$message = str_replace('ZZZ', 12, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Cancelled') {
						// $status_update = DB::table('trips')->where('number', $pending_trip->number)->where('status_id', 3028)->update(['status_id' => 3032, 'reason' => 'You have not submitted the claim,So system Cancelled Automatically']);
						$status_update = DB::table('trips')->where('number', $pending_trip->number)->where('status_id', 3028)->update(['status_id' => 3038, 'reason' => 'You have not submitted the claim,So system Cancelled Automatically', 'updated_at' => Carbon::now()]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_REQUEST_CANCELL'));
						$message = str_replace('YYYY', $pending_trip->end_date, $message);
						$message = str_replace('ZZZ', 15, $message);
						if ($mobile_number && $sendSmsAndMail) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
				} elseif ($status == 'Pending Claim Approval') {
					$detail = 'You have the following Claim waiting for Approval and is / are
pending for more than 2 days. Please approve. In case any of the below claim
request is not desired, then those may be rejected.';
					$content = 'Trip Number -' . $pending_trip->number . ',' . 'Employee Name -' . $pending_trip->employee_name . ',' . 'Trip date -' . $pending_trip->visit_date . ',' . 'Trip From City  -' . $pending_trip->fromcity_name . ',' . 'Trip To City  -' . $pending_trip->tocity_name;
					$subject = 'Pending Claim Approval Mail';
					$arr['detail'] = $detail;
					$arr['content'] = $content;
					$arr['subject'] = $subject;
					$to_email = $arr['to_email'] = Employee::select('employees.id', 'users.email as email', 'users.name as name', 'users.mobile_number')
						->join('users', 'users.entity_id', 'employees.reporting_to_id')
						->where('users.user_type_id', 3121)
						->where('employees.id', $pending_trip->employee_id)
						->get()->toArray();
					foreach ($to_email as $key => $value) {
						$mobile_number = $value['mobile_number'];
						$employee_id = $value['id'];
					}
					if ($title == 'Remainder') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3023)
							->update(['reason' => 'Remainder ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_REMINDER'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 2, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Warning') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3023)
							->update(['reason' => 'Warning ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_WARNING'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 8, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Cancelled') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)->where('status_id', 3023)->update(['status_id' => 3039, 'reason' => 'Your claim is not Approved,So system Rejected Automatically', 'updated_at' => Carbon::now()]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_CANCELL'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 10, $message);
						if ($mobile_number && $sendSmsAndMail) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
				} elseif ($status == 'Pending Divation Claim Approval') {
					$detail = 'You have the following Deviation Claim waiting for Approval and is / are
pending for more than 2 days. Please approve. In case any of the below claim
request is not desired, then those may be rejected.';
					$content = 'Trip Number -' . $pending_trip->number . ',' . 'Employee Name -' . $pending_trip->employee_name . ',' . 'Trip date -' . $pending_trip->visit_date . ',' . 'Trip From City  -' . $pending_trip->fromcity_name . ',' . 'Trip To City  -' . $pending_trip->tocity_name;
					$subject = 'Pending Deviation Claim Approval Mail';
					$arr['detail'] = $detail;
					$arr['content'] = $content;
					$arr['subject'] = $subject;
					$to_email = $arr['to_email'] = EmployeeClaim::join('employees as e', 'e.id', 'ey_employee_claims.employee_id', 'users.mobile_number')
						->join('employees as trip_manager_employee', 'trip_manager_employee.id', 'e.reporting_to_id')
						->join('employees as se_manager_employee', 'se_manager_employee.id', 'trip_manager_employee.reporting_to_id')
						->join('users', 'users.entity_id', 'se_manager_employee.id')
						->where('users.user_type_id', 3121)
						->select('e.id', 'users.email as email', 'users.name as name', 'users.mobile_number')
						->get()->toArray();
					foreach ($to_email as $key => $value) {
						$mobile_number = $value['mobile_number'];
						$employee_id = $value['id'];
					}
					if ($title == 'Remainder') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3029)
							->update(['reason' => 'Remainder ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_REMINDER'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 2, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Warning') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)
							->where('status_id', 3029)
							->update(['reason' => 'Warning ' . date('d-m-Y')]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_WARNING'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 5, $message);
						if ($mobile_number) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
					if ($title == 'Cancelled') {
						$status_update = DB::table('trips')->where('number', $pending_trip->number)->where('status_id', 3029)->update(['status_id' => 3039, 'reason' => 'Your claim is not Approved by senior Manager,So system Rejected Automatically', 'updated_at' => Carbon::now()]);
						$message = str_replace('XXXXXX', $pending_trip->number, config('custom.SMS_TEMPLATES.TRIP_CLAIM_CANCELL'));
						$message = str_replace('YYYY', $pending_trip->created_at, $message);
						$message = str_replace('ZZZ', 10, $message);
						if ($mobile_number && $sendSmsAndMail) {
							sendNotificationTxtMsg($employee_id, $message, $mobile_number);
						}
					}
				}
				$cc_email = $arr['cc_email'] = [];
				$arr['base_url'] = URL::to('/');
				$arr['title'] = $title;
				$arr['status'] = $status;
				foreach ($to_email as $key => $value) {
					$arr['name'] = $value['name'];
					if ($value['email'] && $value['email'] != '-') {
						$email_to = $value['email'];
					}

				}
				$view_name = 'mail.report_mail';
				if ($sendSmsAndMail && count($email_to) > 0) {
					Mail::send(['html' => $view_name], $arr, function ($message) use ($subject, $cc_email, $email_to) {
						$message->to($email_to)->subject($subject);
						$message->cc($cc_email)->subject($subject);
						$message->from('travelex@tvs.in');
					});
				}
			}
			\Log::info('Pending Outstation trip mail completed');
		} else {
			\Log::info('No pending outstation trips.');
		}
		return 'true';
	}
	// Pending outstation trip mail by Karthick T on 15-02-2022

	public static function getPreviousEndKm($request) {
		$trip = Trip::select([
			'visit_bookings.km_end as km_end',
		])
			->join('visits', 'visits.trip_id', 'trips.id')
			->join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
			->where('visit_bookings.id', $request->visit_booking_id)
			->first();
		$end_km = ($trip && $trip->km_end) ? $trip->km_end : null;

		return response()->json(['end_km' => $end_km]);
	}
	// For Attachment by Karthick T on 07-04-2022
	public static function getAttachmentList($trip_id) {
		$exist_attachment_ids = Attachment::where('attachment_type_id', 3200)
			->where('entity_id', $trip_id)
		// ->where('attachment_of_id', '!=', 3754)
			// ->whereNotIn('attachment_of_id', [3754, 3752])
			// ->whereNotIn('attachment_of_id', [3754, 3752, 3751])
			->whereNotIn('attachment_of_id', [3754, 3752, 3751, 3755])
			->pluck('attachment_of_id')->toArray();
		$pending_attachment_lists = Collect(
			Config::select('id', 'name')
				->where('config_type_id', 541)
				->whereNotIn('id', $exist_attachment_ids)
				->orderBy('id', 'ASC')
				->get()
		)->prepend(['id' => null, 'name' => 'Select Any Type']);
		return $pending_attachment_lists;
	}
	// For Attachment by Karthick T on 07-04-2022
	public static function saveVerifierClaim($request) {
		//dd($request->booked_by);
		try {
			DB::beginTransaction();
			if (!empty($request->visits)) {
				foreach ($request->visits as $visit_data) {
					if (!empty($visit_data['id'] && $visit_data['booked_by'] == 'Self')) {
						$visit = Visit::find($visit_data['id']);
						$visit_booking = VisitBooking::firstOrNew(['visit_id' => $visit_data['id']]);
						$visit_booking->visit_id = $visit_data['id'];
						$visit_booking->amount = $visit_data['amount'];
						$visit_booking->gstin = $visit_data['gstin'];
						$visit_booking->tax_percentage = $visit_data['tax_percentage'];
						//$visit_booking->tax = $visit_data['tax'];
						$visit_booking->cgst = $visit_data['cgst'];
						$visit_booking->sgst = $visit_data['sgst'];
						$visit_booking->igst = $visit_data['igst'];
						$visit_booking->igst = $visit_data['gstin_name'];
						$visit_booking->igst = $visit_data['gstin_state_code'];
						$visit_booking->igst = $visit_data['gstin_address'];

						if (!empty($visit_data['round_off']) && ($visit_data['round_off'] > 1 || $visit_data['round_off'] < -1)) {
							return response()->json(['success' => false, 'errors' => ['Round off amount limit is +1 Or -1']]);
						} else {
							$visit_booking->round_off = $visit_data['round_off'];
						}
						$visit_booking->other_charges = $visit_data['other_charges'];
						$visit_booking->save();
						$transport_total = 0;
						$transport_total_amount = 0;
						if ($visit_booking) {
							$transport_total = (float) $visit_booking->amount + (float) $visit_booking->cgst + (float) $visit_booking->sgst + (float) $visit_booking->igst + (float) $visit_booking->toll_fee + (float) $visit_booking->round_off + (float) $visit_booking->other_charges;
							$transport_total_amount += $transport_total;
						}
						$visit_booking->total = $transport_total_amount ? $transport_total_amount : 0;
						$visit_booking->save();
						$visit->save();
						$total_transport_amount = Visit::select(
							DB::raw('SUM(COALESCE(visit_bookings.total ,0)) as total'))
							->join('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
							->where('visits.booking_method_id', 3040)
							->where('visits.trip_id', $request->trip_id)
							->get()->first();
						//SAVE EMPLOYEE CLAIMS
						$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $request->trip_id]);
						$employee_claim->trip_id = $request->trip_id;
						$employee_claim->transport_total = $total_transport_amount->total;
						//$employee_claim->employee_id = Auth::user()->entity_id;
						$employee_claim->created_by = Auth::user()->id;
						$employee_claim->total_amount = 0;
						$employee_claim->save();
						$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
						$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
						$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
						$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
						$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;

						$employee_claim->total_trip_days = $request->trip_total_days;
						$employee_claim->total_amount = $total_amount;
						$employee_claim->save();
						$trip = Trip::where('id', $request->trip_id)->select('advance_received')->get()->first();
						//To Find Amount to Pay Financier or Employee
						if ($trip->advance_received) {
							if ($trip->advance_received > $total_amount) {
								$balance_amount = $trip->advance_received - $total_amount;
								$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
								$employee_claim->amount_to_pay = 2;
							} else {
								$balance_amount = $total_amount - $trip->advance_received;
								$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
								$employee_claim->amount_to_pay = 1;
							}
						} else {
							$employee_claim->balance_amount = $total_amount ? $total_amount : 0;
							$employee_claim->amount_to_pay = 1;
						}

						// dump($trip->advance_received);
						// dump($request->claim_total_amount);

						$employee_claim->save();
						DB::commit();
						return response()->json(['success' => true, 'message' => 'Trip updated successfully!', 'visit' => $visit]);
					}
				}
			}
			if (!empty($request->lodgings)) {
				//dd($request->all());
				foreach ($request->lodgings as $lodgeKey => $lodging_data) {
					if (!empty($lodging_data['id'])) {
						if (isset($lodging_data['id'])) {
							$lodging = Lodging::where('id', $lodging_data['id'])->first();
							if (!$lodging) {
								$lodging = new Lodging;
							}
						} else {
							$lodging = new Lodging;
						}
						$lodging->id = $lodging_data['id'];
						$lodging->amount = $lodging_data['amount'];
						$lodging->gstin = $lodging_data['gstin'];
						$lodging->tax_percentage = $lodging_data['tax_percentage'];
						$lodging->cgst = $lodging_data['cgst'];
						$lodging->sgst = $lodging_data['sgst'];
						$lodging->igst = $lodging_data['igst'];
						$lodging->gstin_state_code = $lodging_data['gstin_state_code'];
						$lodging->gstin_address = $lodging_data['gstin_address'];
						if (!empty($lodging_data['round_off']) && ($lodging_data['round_off'] > 1 || $lodging_data['round_off'] < -1)) {
							return response()->json(['success' => false, 'errors' => ['Round off amount limit is +1 Or -1']]);
						} else {
							$lodging->round_off = $lodging_data['round_off'] ? $lodging_data['round_off'] : 0;
						}
						$lodging->lodge_name = $lodging_data['lodge_name'];
						$lodging->save();
						$lodging_total = 0;
						$lodging_total_amount = 0;
						if ($lodging) {
							$lodging_total = $lodging->amount + $lodging->cgst + $lodging->sgst + $lodging->igst + $lodging->round_off;
							$lodging_total_amount += $lodging_total ? $lodging_total : 0;
						}
						$lodging->total = $lodging_total_amount ? $lodging_total_amount : 0;
						$lodging->save();
						//LODGE TAX INVOICE SAVE

						$lodging->taxInvoices()->forceDelete();

						//ONLY FOR LODGE STAY TYPE
						if ($lodging_data['stay_type_id'] == '3340' && $lodging_data['has_multiple_tax_invoice'] == 'Yes') {
							if (empty($lodging_data['tax_invoice_amount'])) {
								return response()->json([
									'success' => false,
									'errors' => [
										"Lodge tax invoice amount is required. Kindly fill in the tax invoice details form.",
									],
								]);
							}
							//dd($lodging_data['invoice_amount'],$lodging_data['total']);
							/*if (floatval($lodging_data['invoice_amount']) != floatval($lodging_data['total'])) {
								return response()->json([
									'success' => false,
									'errors' => [
										"Lodge invoice amount not matched with the total amount",
									],
								]);
							}*/

							$lodging->has_multiple_tax_invoice = 1;
							$lodging->tax_invoice_amount = !empty($lodging_data['tax_invoice_amount']) ? $lodging_data['tax_invoice_amount'] : NULL;
							$lodging->save();

							//SAVE LODGE TAX INVOICE
							$lodgeTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3771,
							]);
							//NEW
							if (!$lodgeTaxInvoice->exists) {
								$lodgeTaxInvoice->created_at = Carbon::now();
							} else {
								$lodgeTaxInvoice->updated_at = Carbon::now();
							}
							$lodgeTaxInvoice->without_tax_amount = !empty($lodging_data['lodgingTaxInvoice']['without_tax_amount']) ? $lodging_data['lodgingTaxInvoice']['without_tax_amount'] : 0;
							$lodgeTaxInvoice->tax_percentage = !empty($lodging_data['lodgingTaxInvoice']['tax_percentage']) ? $lodging_data['lodgingTaxInvoice']['tax_percentage'] : 0;
							$lodgeTaxInvoice->cgst = !empty($lodging_data['lodgingTaxInvoice']['cgst']) ? $lodging_data['lodgingTaxInvoice']['cgst'] : 0;
							$lodgeTaxInvoice->sgst = !empty($lodging_data['lodgingTaxInvoice']['sgst']) ? $lodging_data['lodgingTaxInvoice']['sgst'] : 0;
							$lodgeTaxInvoice->igst = !empty($lodging_data['lodgingTaxInvoice']['igst']) ? $lodging_data['lodgingTaxInvoice']['igst'] : 0;
							$lodgeTaxInvoice->total = !empty($lodging_data['lodgingTaxInvoice']['total']) ? $lodging_data['lodgingTaxInvoice']['total'] : 0;
							$lodgeTaxInvoice->save();

							//SAVE DRYWASH TAX INVOICE
							$drywashTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3772,
							]);
							//NEW
							if (!$drywashTaxInvoice->exists) {
								$drywashTaxInvoice->created_at = Carbon::now();
							} else {
								$drywashTaxInvoice->updated_at = Carbon::now();
							}
							$drywashTaxInvoice->without_tax_amount = !empty($lodging_data['drywashTaxInvoice']['without_tax_amount']) ? $lodging_data['drywashTaxInvoice']['without_tax_amount'] : 0;
							$drywashTaxInvoice->tax_percentage = !empty($lodging_data['drywashTaxInvoice']['tax_percentage']) ? $lodging_data['drywashTaxInvoice']['tax_percentage'] : 0;
							$drywashTaxInvoice->cgst = !empty($lodging_data['drywashTaxInvoice']['cgst']) ? $lodging_data['drywashTaxInvoice']['cgst'] : 0;
							$drywashTaxInvoice->sgst = !empty($lodging_data['drywashTaxInvoice']['sgst']) ? $lodging_data['drywashTaxInvoice']['sgst'] : 0;
							$drywashTaxInvoice->igst = !empty($lodging_data['drywashTaxInvoice']['igst']) ? $lodging_data['drywashTaxInvoice']['igst'] : 0;
							$drywashTaxInvoice->total = !empty($lodging_data['drywashTaxInvoice']['total']) ? $lodging_data['drywashTaxInvoice']['total'] : 0;
							$drywashTaxInvoice->save();

							//SAVE BOARDING TAX INVOICE
							$boardingTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3773,
							]);
							//NEW
							if (!$boardingTaxInvoice->exists) {
								$boardingTaxInvoice->created_at = Carbon::now();
							} else {
								$boardingTaxInvoice->updated_at = Carbon::now();
							}
							$boardingTaxInvoice->without_tax_amount = !empty($lodging_data['boardingTaxInvoice']['without_tax_amount']) ? $lodging_data['boardingTaxInvoice']['without_tax_amount'] : 0;
							$boardingTaxInvoice->tax_percentage = !empty($lodging_data['boardingTaxInvoice']['tax_percentage']) ? $lodging_data['boardingTaxInvoice']['tax_percentage'] : 0;
							$boardingTaxInvoice->cgst = !empty($lodging_data['boardingTaxInvoice']['cgst']) ? $lodging_data['boardingTaxInvoice']['cgst'] : 0;
							$boardingTaxInvoice->sgst = !empty($lodging_data['boardingTaxInvoice']['sgst']) ? $lodging_data['boardingTaxInvoice']['sgst'] : 0;
							$boardingTaxInvoice->igst = !empty($lodging_data['boardingTaxInvoice']['igst']) ? $lodging_data['boardingTaxInvoice']['igst'] : 0;
							$boardingTaxInvoice->total = !empty($lodging_data['boardingTaxInvoice']['total']) ? $lodging_data['boardingTaxInvoice']['total'] : 0;
							$boardingTaxInvoice->save();

							//SAVE OTHERS TAX INVOICE
							$othersTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3774,
							]);
							//NEW
							if (!$othersTaxInvoice->exists) {
								$othersTaxInvoice->created_at = Carbon::now();
							} else {
								$othersTaxInvoice->updated_at = Carbon::now();
							}
							$othersTaxInvoice->without_tax_amount = !empty($lodging_data['othersTaxInvoice']['without_tax_amount']) ? $lodging_data['othersTaxInvoice']['without_tax_amount'] : 0;
							$othersTaxInvoice->tax_percentage = !empty($lodging_data['othersTaxInvoice']['tax_percentage']) ? $lodging_data['othersTaxInvoice']['tax_percentage'] : 0;
							$othersTaxInvoice->cgst = !empty($lodging_data['othersTaxInvoice']['cgst']) ? $lodging_data['othersTaxInvoice']['cgst'] : 0;
							$othersTaxInvoice->sgst = !empty($lodging_data['othersTaxInvoice']['sgst']) ? $lodging_data['othersTaxInvoice']['sgst'] : 0;
							$othersTaxInvoice->igst = !empty($lodging_data['othersTaxInvoice']['igst']) ? $lodging_data['othersTaxInvoice']['igst'] : 0;
							$othersTaxInvoice->total = !empty($lodging_data['othersTaxInvoice']['total']) ? $lodging_data['othersTaxInvoice']['total'] : 0;
							$othersTaxInvoice->save();

							//SAVE ROUNDOFF TAX INVOICE
							$roundoffTaxInvoice = LodgingTaxInvoice::firstOrNew([
								'lodging_id' => $lodging->id,
								'type_id' => 3775,
							]);
							//NEW
							if (!$roundoffTaxInvoice->exists) {
								$roundoffTaxInvoice->created_at = Carbon::now();
							} else {
								$roundoffTaxInvoice->updated_at = Carbon::now();
							}
							$roundoffTaxInvoice->without_tax_amount = !empty($lodging_data['roundoffTaxInvoice']['without_tax_amount']) ? $lodging_data['roundoffTaxInvoice']['without_tax_amount'] : 0;
							$roundoffTaxInvoice->cgst = !empty($lodging_data['roundoffTaxInvoice']['cgst']) ? $lodging_data['roundoffTaxInvoice']['cgst'] : 0;
							$roundoffTaxInvoice->sgst = !empty($lodging_data['roundoffTaxInvoice']['sgst']) ? $lodging_data['roundoffTaxInvoice']['sgst'] : 0;
							$roundoffTaxInvoice->igst = !empty($lodging_data['roundoffTaxInvoice']['igst']) ? $lodging_data['roundoffTaxInvoice']['igst'] : 0;
							$roundoffTaxInvoice->total = !empty($lodging_data['roundoffTaxInvoice']['total']) ? $lodging_data['roundoffTaxInvoice']['total'] : 0;
							$roundoffTaxInvoice->save();
						}
						//SAVE EMPLOYEE CLAIMS
						$total_lodge_amount = Lodging::select(
							DB::raw('SUM(COALESCE(total,0)) as total'))->where('trip_id', $request->trip_id)->get()->first();
						//SAVE EMPLOYEE CLAIMS
						$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $request->trip_id]);
						$employee_claim->lodging_total = $total_lodge_amount->total;
						//$employee_claim->employee_id = Auth::user()->entity_id;
						$employee_claim->created_by = Auth::user()->id;

						$employee_claim->save();

						$transport_amount = $employee_claim->transport_total ? $employee_claim->transport_total : 0;
						$lodging_amount = $employee_claim->lodging_total ? $employee_claim->lodging_total : 0;
						$boarding_amount = $employee_claim->boarding_total ? $employee_claim->boarding_total : 0;
						$local_travel_amount = $employee_claim->local_travel_total ? $employee_claim->local_travel_total : 0;
						$total_amount = $transport_amount + $lodging_amount + $boarding_amount + $local_travel_amount;
						// $employee_claim->total_trip_days = $request->trip_total_days;
						$employee_claim->total_amount = $total_amount;
						$employee_claim->save();
						//To Find Amount to Pay Financier or Employee
						$trip = Trip::where('id', $request->trip_id)->select('advance_received')->get()->first();
						if ($trip->advance_received) {
							if ($trip->advance_received > $total_amount) {
								$balance_amount = $trip->advance_received - $total_amount;
								$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
								$employee_claim->amount_to_pay = 2;
							} else {
								$balance_amount = $total_amount - $trip->advance_received;
								$employee_claim->balance_amount = $balance_amount ? $balance_amount : 0;
								$employee_claim->amount_to_pay = 1;
							}
						} else {
							$employee_claim->balance_amount = $total_amount ? $total_amount : 0;
							$employee_claim->amount_to_pay = 1;
						}

						$employee_claim->save();
						//GET SAVED LODGINGS
						$saved_lodgings = Trip::with([
							'lodgings',
							'lodgings.lodgingTaxInvoice',
							'lodgings.drywashTaxInvoice',
							'lodgings.boardingTaxInvoice',
							'lodgings.othersTaxInvoice',
							'lodgings.roundoffTaxInvoice',
							'lodging_attachments',
							'lodgings.city',
							'lodgings.stateType',
							'lodgings.attachments',
							'lodgings.sharingType',
							'lodgings.shareDetails',
							'lodgings.shareDetails.employee',
							'lodgings.shareDetails.employee.user',
							'lodgings.shareDetails.employee.outlet',
							'lodgings.shareDetails.employee.grade',
							'lodgings.shareDetails.employee.designation',
							'lodgings.shareDetails.employee.Sbu',
						])->find($request->trip_id);
						DB::commit();

						return response()->json(['success' => true, 'saved_lodgings' => $saved_lodgings]);
					}
				}
			}
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage() . $e->getLine()]]);
		}
	}

	public static function searchLodgeSharingEmployee($request) {
		$key = $request->key;
		$data['search_data'] = Employee::select(
			'employees.id',
			'users.name',
			'employees.code'
		)
			->join('users', 'users.entity_id', 'employees.id')
			->where(function ($q) use ($key) {
				$q->where('employees.code', 'like', '%' . $key . '%')
					->orWhere('users.name', 'like', '%' . $key . '%')
				;
			})
			->where('users.user_type_id', 3121) //EMPLOYEE
			->where('users.id', '!=', Auth::id())
			->where('employees.company_id', Auth::user()->company_id)
			->get();
		return response()->json($data);
	}

	public static function getLodgeSharingEmployee($request) {
		try {
			$validator = Validator::make($request->all(), [
				'employee_id' => [
					'required',
					'exists:employees,id',
				],
				'city_id' => [
					'required',
					'exists:ncities,id',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$data['employee'] = Employee::select([
				'employees.id as employee_id',
				'employees.code as employee_code',
				'employees.grade_id',
				'outlets.code as outlet_code',
				'outlets.name as outlet_name',
				'users.name as user_name',
				'grades.name as grade',
				'designations.name as designation',
				'sbus.name as sbu',
			])
				->join('outlets', 'outlets.id', 'employees.outlet_id')
				->join('entities as grades', 'grades.id', 'employees.grade_id')
				->leftjoin('designations', 'designations.id', 'employees.designation_id')
				->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
				->join('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121) //EMPLOYEE
				->where('employees.id', $request->employee_id)
				->first();
			$data['employee']->eligible_amount = 0.00;
			$city_category_id = NCity::where('id', $request->city_id)
				->pluck('category_id')
				->first();
			// $data['employee']['normal'] = [
			// 	'eligible_amount' => 0,
			// ];
			if ($city_category_id) {
				$lodge_expense_type = DB::table('grade_expense_type')
					->where('grade_id', $data['employee']->grade_id)
					->where('expense_type_id', 3001) //LODGE EXPENSE
					->where('city_category_id', $city_category_id)
					->first();
				if ($lodge_expense_type) {
					// $data['employee']['normal'] = [
					// 	'eligible_amount' => $lodge_expense_type->eligible_amount,
					// ];
					$data['employee']->eligible_amount = $lodge_expense_type->eligible_amount;
				}
			}

			return response()->json([
				'success' => true,
				'data' => $data,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(),
				],
			]);
		}
	}

	public function generatePrePaymentApOracleAxapta() {
		//VENDOR
		$res = [];
		$res['success'] = false;
		$res['errors'] = [];

		$companyId = $this->company_id;
		// $companyBusinessUnit = isset($this->company->oem_business_unit->name) ? $this->company->oem_business_unit->name : null;
		// $companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;

		// $transactionDetail = $this->company ? $this->company->prePaymentInvoiceTransaction() : null;
		if(!empty($this->employee->department) && $this->employee->department->business_id == 2){
			// $transactionDetail = $this->company ? $this->company->oeslPrePaymentInvoiceTransaction() : null;
			$transactionDetail = $this->company ? $this->company->oeslPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($this->company->oes_business_unit->name) ? $this->company->oes_business_unit->name : null;
			$companyCode = isset($this->company->oes_business_unit->code) ? $this->company->oes_business_unit->code : null;
		}else if(!empty($this->employee->department) && $this->employee->department->business_id == 3){
			$transactionDetail = $this->company ? $this->company->hondaPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($this->company->oem_business_unit->name) ? $this->company->oem_business_unit->name : null;
			$companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;
		}else if(!empty($this->employee->department) && $this->employee->department->business_id == 8){
			$transactionDetail = $this->company ? $this->company->investmentPrePaymentInvoiceTransaction() : null;
			$companyBusinessUnit = isset($this->company->investment_business_unit->name) ? $this->company->investment_business_unit->name : null;
			$companyCode = isset($this->company->investment_business_unit->code) ? $this->company->investment_business_unit->code : null;
		}else{
			// $transactionDetail = $this->company ? $this->company->prePaymentInvoiceTransaction() : null;
			$transactionDetail = $this->company ? $this->company->prePaymentInvoiceTransaction() : null;

			$companyBusinessUnit = isset($this->company->oem_business_unit->name) ? $this->company->oem_business_unit->name : null;
			$companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;
		}
		// $invoiceSource = 'Pre Payment Invoice';
		$invoiceSource = 'Travelex';
		$documentType = 'Invoice';
		if (!empty($transactionDetail)) {
			// $invoiceSource = $transactionDetail->type ? $transactionDetail->type : $invoiceSource;
			$invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
			$documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
		}

		$businessUnitName = $companyBusinessUnit;
		$invoiceNumber = $this->number;

		$tripApprovalLog = ApprovalLog::select([
			'id',
			DB::raw('DATE_FORMAT(approved_at,"%Y-%m-%d") as approved_date'),
		])
			->where('type_id', 3581) //Outstation Trip
			->where('approval_type_id', 3600) //Outstation Trip - Manager Approved
			->where('entity_id', $this->id)
			->first();
		$tripManagerApprovedDate = null;
		if($tripApprovalLog){
			$tripManagerApprovedDate = $tripApprovalLog->approved_date;
		}

		// $invoiceDate = $this->created_at ? date("Y-m-d", strtotime($this->created_at)) : null;
		$invoiceDate = $tripManagerApprovedDate;
		$employeeData = $this->employee;
		$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
		// $invoiceType = 'Standard';
		$invoiceType = 'Prepayment';
		$description = '';
		if (!empty($employeeData->code)) {
			$description .= $employeeData->code;
		}
		if (!empty($employeeData->user->name)) {
			$description .= ',' . ($employeeData->user->name);
		}
		if (!empty($this->purpose)) {
			$description .= ',' . ($this->purpose->name);
		}

		$description .= ',Travel Date : ' . date('Y-m-d', strtotime($this->start_date)) .' to '. date('Y-m-d', strtotime($this->end_date));
		$tripLocations = Visit::select([
			DB::raw("CASE 
				WHEN tocity.type_id = 4111 and visits.other_city is not null 
				THEN visits.other_city 
				WHEN tocity.type_id = 4111 and visits.other_city is null 
				THEN tocity.name
				ELSE tocity.name 
				END as location"),
		])
			->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
			->whereNotIn('visits.status_id', [3062]) //Cancelled
			->where('visits.trip_id', $this->id)
			->orderBy('visits.id')
			->get()
			->implode('location', ',');

		if($tripLocations){
			$description .= ',Travel Place : ' . ($tripLocations);
		}

		$amount = $this->advance_received;
		// $outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
		$outletCode = $this->branch ? $this->branch->oracle_code_l2 : null;
		// $accountingClass = 'Payable';
		$accountingClass = 'Purchase/Expense';
		$company = $this->company ? $this->company->oracle_code : '';

		$sbu = $employeeData->Sbu;
		$lob = $department = null;
		if ($sbu) {
			$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
			$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
		}
		$location = $outletCode;
		$naturalAccount = Config::where('id', 3860)->first()->name;
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

		//ROUND OFF
		// $amountDiff = 0;
		// if (!empty($amount)) {
		// $amountDiff = number_format((round($amount) - $amount), 2);
		// }

		DB::table('oracle_ap_invoice_exports')->insert([
			'company_id' => $companyId,
			'business_unit' => $businessUnitName,
			'invoice_source' => $invoiceSource,
			'invoice_number' => $invoiceNumber,
			'invoice_date' => $invoiceDate,
			'supplier_number' => $supplierNumber,
			'supplier_site_name' => $supplierSiteName,
			'invoice_type' => $invoiceType,
			// 'description' => $description,
			'invoice_description' => $description,
			'amount' => round($amount),
			'outlet' => $outletCode,
			// 'round_off_amount' => $amountDiff,
			'accounting_class' => $accountingClass,
			'company' => $companyCode,
			'lob' => $lob,
			'location' => $location,
			'department' => $department,
			'natural_account' => $naturalAccount,
			'document_type' => $documentType,
			'accounting_date' => $invoiceDate,
			'created_at' => Carbon::now(),
		]);

		$res['success'] = true;
		DB::setDefaultConnection('mysql');
		return $res;
	}

	// public function generateInvoiceArOracleAxapta() {
	// 	$res = [];
	// 	$res['success'] = false;
	// 	$res['errors'] = [];

	// 	$companyId = $this->company_id;
	// 	$companyBusinessUnit = isset($this->company->oem_business_unit->name) ? $this->company->oem_business_unit->name : null;
	// 	$companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;
	// 	$transactionClass = '';
	// 	$transactionBatchName = 'Travelex';
	// 	$transactionTypeName = 'Invoice';
	// 	$transactionDetail = $this->company ? $this->company->invoiceTransaction() : null;
	// 	if (!empty($transactionDetail)) {
	// 		$transactionClass = $transactionDetail->class ? $transactionDetail->class : $transactionClass;
	// 		$transactionBatchName = $transactionDetail->batch ? $transactionDetail->batch : $transactionBatchName;
	// 		$transactionTypeName = $transactionDetail->type ? $transactionDetail->type : $transactionTypeName;
	// 	}

	// 	$prePaymentTransactionDetail = $this->company ? $this->company->prePaymentInvoiceTransaction() : null;
	// 	$prePaymentClass = '';
	// 	$prePaymentBatch = 'Travelex';
	// 	$prePaymentType = 'Pre Payment Invoice';
	// 	if (!empty($prePaymentTransactionDetail)) {
	// 		$prePaymentClass = $prePaymentTransactionDetail->class ? $prePaymentTransactionDetail->class : $prePaymentClass;
	// 		$prePaymentBatch = $prePaymentTransactionDetail->batch ? $prePaymentTransactionDetail->batch : $prePaymentBatch;
	// 		$prePaymentType = $prePaymentTransactionDetail->type ? $prePaymentTransactionDetail->type : $prePaymentType;
	// 	}

	// 	$businessUnitName = $companyBusinessUnit;

	// 	$employeeData = $this->employee;
	// 	$customerCode = $employeeData ? $employeeData->code : null;
	// 	$supplierNumber = $employeeData ? $employeeData->code : null;
	// 	$outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
	// 	$customerSiteNumber = $outletCode;
	// 	$shipToCustomerAccount = $customerCode;
	// 	$description = '';
	// 	if (!empty($employeeData->code)) {
	// 		$description .= $employeeData->code;
	// 	}
	// 	if (!empty($employeeData->user->name)) {
	// 		$description .= ' - ' . ($employeeData->user->name);
	// 	}
	// 	if (!empty($this->purpose)) {
	// 		$description .= ' - ' . ($this->purpose->name);
	// 	}

	// 	$employeeClaim = EmployeeClaim::select([
	// 		'id',
	// 		'number',
	// 		'total_amount',
	// 		'boarding_total',
	// 		'local_travel_total',
	// 		'amount_to_pay',
	// 		'balance_amount',
	// 		'created_at',
	// 	])
	// 		->where('trip_id', $this->id)
	// 		->first();

	// 	$invoiceAmount = null;
	// 	$transactionNumber = null;
	// 	$invoiceDate = null;
	// 	$invoiceNumber = null;
	// 	if ($employeeClaim) {
	// 		$invoiceAmount = round($employeeClaim->total_amount);
	// 		$transactionNumber = $employeeClaim->number;
	// 		$invoiceDate = $employeeClaim->created_at ? date("Y-m-d", strtotime($employeeClaim->created_at)) : null;
	// 		$invoiceNumber = $employeeClaim->number;
	// 	}

	// 	$employeeTrip = $this;
	// 	$employeeTransportAmount = 0.00;
	// 	$employeeTransportOtherCharges = 0.00;
	// 	$selfVisitTotalRoundOff = 0;
	// 	$employeeTransportTaxableValue = 0.00;

	// 	$employeeLodgingTaxableValue = 0.00;
	// 	$employeeBoardingTaxableValue = 0.00;
	// 	$employeeLocalTravelTaxableValue = 0.00;
	// 	$employeeTotalTaxableValue = 0.00;

	// 	if ($employeeTrip->selfVisits->isNotEmpty()) {
	// 		foreach ($employeeTrip->selfVisits as $selfVisit) {
	// 			if ($selfVisit->booking) {
	// 				$employeeTransportAmount += floatval($selfVisit->booking->amount);
	// 				$employeeTransportOtherCharges += floatval($selfVisit->booking->other_charges);
	// 				$selfVisitTotalRoundOff += floatval($selfVisit->booking->round_off);
	// 			}
	// 		}
	// 	}
	// 	$employeeTransportTaxableValue = floatval($employeeTransportAmount + $employeeTransportOtherCharges);

	// 	//LODGING
	// 	if ($employeeTrip->lodgings->isNotEmpty()) {
	// 		$employeeLodgingTaxableValue = floatval($employeeTrip->lodgings()->sum('amount'));
	// 	}

	// 	//BOARDING
	// 	if ($employeeTrip->boardings->isNotEmpty()) {
	// 		$employeeBoardingTaxableValue = floatval($employeeClaim->boarding_total);
	// 	}

	// 	//LOCAL TRAVELS
	// 	if ($employeeTrip->localTravels->isNotEmpty()) {
	// 		$employeeLocalTravelTaxableValue = floatval($employeeClaim->local_travel_total);
	// 	}

	// 	$employeeTotalTaxableValue = floatval($employeeTransportTaxableValue + $employeeLodgingTaxableValue + $employeeBoardingTaxableValue + $employeeLocalTravelTaxableValue + $selfVisitTotalRoundOff);

	// 	$unitPrice = $employeeTotalTaxableValue;
	// 	$amount = $employeeTotalTaxableValue;
	// 	$quantity = 1;
	// 	$accountingClass = 'REV';
	// 	$sbu = $employeeData->Sbu;
	// 	$lob = $costCentre = $department = null;
	// 	if ($sbu) {
	// 		$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
	// 		$costCentre = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
	// 		$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
	// 	}

	// 	$naturalAccount = Config::where('id', 3861)->first()->name;
	// 	$location = $outletCode;

	// 	$invoiceType = 'Standard';
	// 	$company = $this->company ? $this->company->oracle_code : '';

	// 	$bpas_portal = Portal::select([
	// 		'db_host_name',
	// 		'db_port_number',
	// 		'db_name',
	// 		'db_user_name',
	// 		'db_password',
	// 	])
	// 		->where('id', 1)
	// 		->first();
	// 	DB::setDefaultConnection('dynamic');
	// 	$db_host_name = dataBaseConfig::set('database.connections.dynamic.host', $bpas_portal->db_host_name);
	// 	$db_port_number = dataBaseConfig::set('database.connections.dynamic.port', $bpas_portal->db_port_number);
	// 	$db_port_driver = dataBaseConfig::set('database.connections.dynamic.driver', "mysql");
	// 	$db_name = dataBaseConfig::set('database.connections.dynamic.database', $bpas_portal->db_name);
	// 	$db_username = dataBaseConfig::set('database.connections.dynamic.username', $bpas_portal->db_user_name);
	// 	$db_username = dataBaseConfig::set('database.connections.dynamic.password', $bpas_portal->db_password);
	// 	DB::purge('dynamic');
	// 	DB::reconnect('dynamic');

	// 	$arInvoiceExports = DB::table('oracle_ar_invoice_exports')->where([
	// 		'transaction_number' => $transactionNumber,
	// 		'business_unit' => $companyBusinessUnit,
	// 		'transaction_type_name' => $transactionTypeName,
	// 	])->get();
	// 	if (count($arInvoiceExports) > 0) {
	// 		$res['errors'] = ['Already exported to oracle table'];
	// 		return $res;
	// 	}

	// 	//LODGING TAXES
	// 	$cgstAmount = 0;
	// 	$sgstAmount = 0;
	// 	$igstAmount = 0;
	// 	$cgstSgstPercentage = 0;
	// 	$igstPercentage = 0;

	// 	if ($employeeTrip->lodgings->isNotEmpty()) {
	// 		foreach ($employeeTrip->lodgings as $lodging) {
	// 			if ($lodging->stay_type_id == 3340) {
	// 				//HAS MULTIPLE TAX INVOICE
	// 				if ($lodging->has_multiple_tax_invoice == "Yes") {
	// 					//LODGE
	// 					if ($lodging->lodgingTaxInvoice && (($lodging->lodgingTaxInvoice->cgst != '0.00' && $lodging->lodgingTaxInvoice->sgst != '0.00') || ($lodging->lodgingTaxInvoice->igst != '0.00'))) {
	// 						if ($lodging->lodgingTaxInvoice->cgst > 0 && $lodging->lodgingTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->lodgingTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->lodgingTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->lodgingTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->lodgingTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->lodgingTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 					//DRY WASH
	// 					if ($lodging->drywashTaxInvoice && (($lodging->drywashTaxInvoice->cgst != '0.00' && $lodging->drywashTaxInvoice->sgst != '0.00') || ($lodging->drywashTaxInvoice->igst != '0.00'))) {

	// 						if ($lodging->drywashTaxInvoice->cgst > 0 && $lodging->drywashTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->drywashTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->drywashTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->drywashTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->drywashTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->drywashTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 					//BOARDING
	// 					if ($lodging->boardingTaxInvoice && (($lodging->boardingTaxInvoice->cgst != '0.00' && $lodging->boardingTaxInvoice->sgst != '0.00') || ($lodging->boardingTaxInvoice->igst != '0.00'))) {
	// 						if ($lodging->boardingTaxInvoice->cgst > 0 && $lodging->boardingTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->boardingTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->boardingTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->boardingTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->boardingTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->boardingTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 					//OTHERS
	// 					if ($lodging->othersTaxInvoice && (($lodging->othersTaxInvoice->cgst != '0.00' && $lodging->othersTaxInvoice->sgst != '0.00') || ($lodging->othersTaxInvoice->igst != '0.00'))) {
	// 						if ($lodging->othersTaxInvoice->cgst > 0 && $lodging->othersTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->othersTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->othersTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->othersTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->othersTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->othersTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 				} else {
	// 					//SINGLE
	// 					if ($lodging && (($lodging->cgst != '0.00' && $lodging->sgst != '0.00') || ($lodging->igst != '0.00'))) {
	// 						if ($lodging->cgst > 0 && $lodging->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->cgst);
	// 							$sgstAmount += floatval($lodging->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->igst);
	// 							$igstPercentage += floatval($lodging->tax_percentage);
	// 						}
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}

	// 	//GST TAX CLASSIFICATION
	// 	$taxClassification = '';
	// 	if ($cgstAmount > 0 && $sgstAmount > 0) {
	// 		$taxClassification .= 'CGST + SGST + ' . (round($cgstSgstPercentage));
	// 	}
	// 	if ($igstAmount > 0) {
	// 		$taxClassification .= ' IGST + ' . (round($igstPercentage));
	// 	}

	// 	//ROUND OFF
	// 	$employeeLodgingRoundoff = floatval($employeeTrip->lodgings()->sum('round_off'));
	// 	$roundOffAmt = round($employeeTrip->totalAmount) - $employeeTrip->totalAmount;
	// 	$employeeLodgingRoundoff += floatval($roundOffAmt);

	// 	DB::table('oracle_ar_invoice_exports')->insert([
	// 		'company_id' => $companyId,
	// 		'business_unit' => $businessUnitName,
	// 		'transaction_class' => $transactionClass,
	// 		'transaction_batch_source_name' => $transactionBatchName,
	// 		'transaction_type_name' => $transactionTypeName,
	// 		'transaction_number' => $transactionNumber,
	// 		'invoice_amount' => $invoiceAmount,
	// 		'transaction_date' => $invoiceDate,
	// 		'customer_account_number' => $customerCode,
	// 		'bill_to_customer_site_number' => $customerSiteNumber,
	// 		'credit_outlet' => $outletCode,
	// 		'description' => $description,
	// 		'quantity' => $quantity,
	// 		'unit_price' => $unitPrice,
	// 		'amount' => $amount,
	// 		'tax_classification' => $taxClassification,
	// 		'cgst' => $cgstAmount,
	// 		'sgst' => $sgstAmount,
	// 		'igst' => $igstAmount,
	// 		'round_off_amount' => $employeeLodgingRoundoff,
	// 		'accounting_class' => $accountingClass,
	// 		'company' => $companyCode,
	// 		'lob' => $lob,
	// 		'location' => $location,
	// 		'cost_centre' => $costCentre,
	// 		'natural_account' => $naturalAccount,
	// 		'created_at' => Carbon::now(),
	// 	]);

	// 	//IF ADVANCE RECEIVED
	// 	// dd("fdfdf");
	// 	if ($employeeTrip->advance_received > 0) {
	// 		//COMPANY TO EMPLOYEE
	// 		if ($employeeClaim->amount_to_pay == 1) {
	// 			if ($employeeClaim->balance_amount && $employeeClaim->balance_amount != '0.00') {
	// 				//ROUND OFF
	// 				$amountDiff = 0;
	// 				if (!empty($employeeClaim->balance_amount)) {
	// 					$amountDiff = number_format((round($employeeClaim->balance_amount) - $employeeClaim->balance_amount), 2);
	// 				}
	// 				DB::table('oracle_ap_invoice_exports')->insert([
	// 					'company_id' => $companyId,
	// 					'business_unit' => $businessUnitName,
	// 					'invoice_source' => $prePaymentType,
	// 					'invoice_number' => $invoiceNumber,
	// 					'invoice_date' => $invoiceDate,
	// 					'supplier_number' => $supplierNumber,
	// 					'invoice_type' => $invoiceType,
	// 					'description' => $description,
	// 					'amount' => $employeeClaim->balance_amount,
	// 					'outlet' => $outletCode,
	// 					'round_off_amount' => $amountDiff,
	// 					'accounting_class' => 'Payable',
	// 					'company' => $company,
	// 					'lob' => $lob,
	// 					'location' => $location,
	// 					'department' => $department,
	// 					'natural_account' => $naturalAccount,
	// 					'created_at' => Carbon::now(),
	// 				]);
	// 			}
	// 		}

	// 		//EMPLOYEE TO COMPANY
	// 		if ($employeeClaim->amount_to_pay == 2) {
	// 			if ($employeeClaim->balance_amount && $employeeClaim->balance_amount != '0.00') {
	// 				//ROUND OFF
	// 				$amountDiff = 0;
	// 				if (!empty($employeeClaim->balance_amount)) {
	// 					$amountDiff = number_format((round($employeeClaim->balance_amount) - $employeeClaim->balance_amount), 2);
	// 				}

	// 				DB::table('oracle_ar_invoice_exports')->insert([
	// 					'company_id' => $companyId,
	// 					'business_unit' => $businessUnitName,
	// 					'transaction_class' => $prePaymentClass,
	// 					'transaction_batch_source_name' => $prePaymentBatch,
	// 					'transaction_type_name' => $prePaymentType,
	// 					'transaction_number' => $transactionNumber,
	// 					// 'invoice_amount' => $invoiceAmount,
	// 					'transaction_date' => $invoiceDate,
	// 					'customer_account_number' => $customerCode,
	// 					'bill_to_customer_site_number' => $customerSiteNumber,
	// 					'credit_outlet' => $outletCode,
	// 					'description' => $description,
	// 					'quantity' => $quantity,
	// 					'unit_price' => $employeeClaim->balance_amount,
	// 					'amount' => $employeeClaim->balance_amount,
	// 					// 'tax_classification' => $taxClassification,
	// 					// 'cgst' => $cgstAmount,
	// 					// 'sgst' => $sgstAmount,
	// 					// 'igst' => $igstAmount,
	// 					'round_off_amount' => $amountDiff,
	// 					'accounting_class' => 'REV',
	// 					'company' => $companyCode,
	// 					'lob' => $lob,
	// 					'location' => $location,
	// 					'cost_centre' => $costCentre,
	// 					'natural_account' => $naturalAccount,
	// 					'created_at' => Carbon::now(),
	// 				]);
	// 			}
	// 		}
	// 	}

	// 	$res['success'] = true;
	// 	DB::setDefaultConnection('mysql');
	// 	return $res;
	// }

	//OLD FUNCTION
	// public function generateInvoiceApOracleAxapta() {
	// 	$res = [];
	// 	$res['success'] = false;
	// 	$res['errors'] = [];

	// 	$employeeTrip = $this;
	// 	$companyId = $employeeTrip->company_id;
	// 	$companyBusinessUnit = isset($employeeTrip->company->oem_business_unit->name) ? $employeeTrip->company->oem_business_unit->name : null;
	// 	$companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;

	// 	$transactionDetail = $employeeTrip->company ? $employeeTrip->company->invoiceTransaction() : null;
	// 	$invoiceSource = 'Invoice';
	// 	if (!empty($transactionDetail)) {
	// 		$invoiceSource = $transactionDetail->type ? $transactionDetail->type : $invoiceSource;
	// 	}

	// 	$prePaymentTransactionDetail = $employeeTrip->company ? $employeeTrip->company->prePaymentInvoiceTransaction() : null;
	// 	$prePaymentClass = '';
	// 	$prePaymentBatch = 'Travelex';
	// 	$prePaymentType = 'Pre Payment Invoice';
	// 	if (!empty($prePaymentTransactionDetail)) {
	// 		$prePaymentClass = $prePaymentTransactionDetail->class ? $prePaymentTransactionDetail->class : $prePaymentClass;
	// 		$prePaymentBatch = $prePaymentTransactionDetail->batch ? $prePaymentTransactionDetail->batch : $prePaymentBatch;
	// 		$prePaymentType = $prePaymentTransactionDetail->type ? $prePaymentTransactionDetail->type : $prePaymentType;
	// 	}

	// 	$employeeClaim = EmployeeClaim::select([
	// 		'id',
	// 		'number',
	// 		'total_amount',
	// 		'boarding_total',
	// 		'local_travel_total',
	// 		'amount_to_pay',
	// 		'balance_amount',
	// 		'created_at',
	// 	])
	// 		->where('trip_id', $employeeTrip->id)
	// 		->first();

	// 	$invoiceAmount = null;
	// 	$invoiceDate = null;
	// 	$invoiceNumber = null;
	// 	$prePaymentNumber = null;
	// 	$prePaymentDate = null;
	// 	$prePaymentAmount = null;
	// 	if ($employeeClaim) {
	// 		$invoiceAmount = round($employeeClaim->total_amount);
	// 		$invoiceDate = $employeeClaim->created_at ? date("Y-m-d", strtotime($employeeClaim->created_at)) : null;
	// 		$invoiceNumber = $employeeClaim->number;

	// 		if ($employeeTrip->advance_received && $employeeTrip->advance_received > 0) {
	// 			$prePaymentNumber = $employeeTrip->number;
	// 			$prePaymentDate = $employeeTrip->created_at ? date("Y-m-d", strtotime($employeeTrip->created_at)) : null;
	// 			$prePaymentAmount = $employeeTrip->advance_received;
	// 		}
	// 	}

	// 	$businessUnitName = $companyBusinessUnit;
	// 	$employeeData = $employeeTrip->employee;
	// 	$customerCode = $employeeData ? $employeeData->code : null;
	// 	$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
	// 	$invoiceType = 'Standard';
	// 	$description = '';
	// 	if (!empty($employeeData->code)) {
	// 		$description .= $employeeData->code;
	// 	}
	// 	if (!empty($employeeData->user->name)) {
	// 		$description .= ' - ' . ($employeeData->user->name);
	// 	}
	// 	if (!empty($employeeTrip->purpose->name)) {
	// 		$description .= ' - ' . ($employeeTrip->purpose->name);
	// 	}

	// 	$employeeTransportAmount = 0.00;
	// 	$employeeTransportOtherCharges = 0.00;
	// 	$selfVisitTotalRoundOff = 0;
	// 	$employeeTransportTaxableValue = 0.00;

	// 	$employeeLodgingTaxableValue = 0.00;
	// 	$employeeBoardingTaxableValue = 0.00;
	// 	$employeeLocalTravelTaxableValue = 0.00;
	// 	$employeeTotalTaxableValue = 0.00;

	// 	if ($employeeTrip->selfVisits->isNotEmpty()) {
	// 		foreach ($employeeTrip->selfVisits as $selfVisit) {
	// 			if ($selfVisit->booking) {
	// 				$employeeTransportAmount += floatval($selfVisit->booking->amount);
	// 				$employeeTransportOtherCharges += floatval($selfVisit->booking->other_charges);
	// 				$selfVisitTotalRoundOff += floatval($selfVisit->booking->round_off);
	// 			}
	// 		}
	// 	}
	// 	$employeeTransportTaxableValue = floatval($employeeTransportAmount + $employeeTransportOtherCharges);

	// 	//LODGING
	// 	if ($employeeTrip->lodgings->isNotEmpty()) {
	// 		$employeeLodgingTaxableValue = floatval($employeeTrip->lodgings()->sum('amount'));
	// 	}

	// 	//BOARDING
	// 	if ($employeeTrip->boardings->isNotEmpty()) {
	// 		$employeeBoardingTaxableValue = floatval($employeeClaim->boarding_total);
	// 	}

	// 	//LOCAL TRAVELS
	// 	if ($employeeTrip->localTravels->isNotEmpty()) {
	// 		$employeeLocalTravelTaxableValue = floatval($employeeClaim->local_travel_total);
	// 	}

	// 	$amount = floatval($employeeTransportTaxableValue + $employeeLodgingTaxableValue + $employeeBoardingTaxableValue + $employeeLocalTravelTaxableValue + $selfVisitTotalRoundOff);
	// 	$outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
	// 	$customerSiteNumber = $outletCode;
	// 	$accountingClass = 'Payable';
	// 	$company = $employeeTrip->company ? $employeeTrip->company->oracle_code : '';

	// 	$sbu = $employeeData->Sbu;
	// 	$lob = $department = null;
	// 	if ($sbu) {
	// 		$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
	// 		$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
	// 	}
	// 	$location = $outletCode;
	// 	$naturalAccount = Config::where('id', 3861)->first()->name;
	// 	$supplierSiteName = $outletCode;

	// 	$bpas_portal = Portal::select([
	// 		'db_host_name',
	// 		'db_port_number',
	// 		'db_name',
	// 		'db_user_name',
	// 		'db_password',
	// 	])
	// 		->where('id', 1)
	// 		->first();
	// 	DB::setDefaultConnection('dynamic');
	// 	$db_host_name = dataBaseConfig::set('database.connections.dynamic.host', $bpas_portal->db_host_name);
	// 	$db_port_number = dataBaseConfig::set('database.connections.dynamic.port', $bpas_portal->db_port_number);
	// 	$db_port_driver = dataBaseConfig::set('database.connections.dynamic.driver', "mysql");
	// 	$db_name = dataBaseConfig::set('database.connections.dynamic.database', $bpas_portal->db_name);
	// 	$db_username = dataBaseConfig::set('database.connections.dynamic.username', $bpas_portal->db_user_name);
	// 	$db_username = dataBaseConfig::set('database.connections.dynamic.password', $bpas_portal->db_password);
	// 	DB::purge('dynamic');
	// 	DB::reconnect('dynamic');

	// 	$apInvoiceExports = DB::table('oracle_ap_invoice_exports')->where([
	// 		'invoice_number' => $invoiceNumber,
	// 		'business_unit' => $businessUnitName,
	// 		'invoice_source' => $invoiceSource,
	// 	])->get();
	// 	if (count($apInvoiceExports) > 0) {
	// 		$res['errors'] = ['Already exported to oracle table'];
	// 		return $res;
	// 	}

	// 	//LODGING TAXES
	// 	$cgstAmount = 0;
	// 	$sgstAmount = 0;
	// 	$igstAmount = 0;
	// 	$cgstSgstPercentage = 0;
	// 	$igstPercentage = 0;

	// 	//FAIR TAXES
	// 	if ($employeeTrip->selfVisits->isNotEmpty()) {
	// 		foreach ($employeeTrip->selfVisits as $selfVisitData) {
	// 			if ($selfVisitData->booking && !empty($selfVisitData->booking->gstin)) {
	// 				if ($selfVisitData->booking->cgst > 0 && $selfVisitData->booking->sgst > 0) {
	// 					$cgstAmount += floatval($selfVisitData->booking->cgst);
	// 					$sgstAmount += floatval($selfVisitData->booking->sgst);
	// 					$cgstSgstPercentage += floatval($selfVisitData->booking->tax_percentage);
	// 				} else {
	// 					$igstAmount += floatval($selfVisitData->booking->igst);
	// 					$igstPercentage += floatval($selfVisitData->booking->tax_percentage);
	// 				}
	// 			}
	// 		}
	// 	}

	// 	if ($employeeTrip->lodgings->isNotEmpty()) {
	// 		foreach ($employeeTrip->lodgings as $lodging) {
	// 			if ($lodging->stay_type_id == 3340) {
	// 				//HAS MULTIPLE TAX INVOICE
	// 				if ($lodging->has_multiple_tax_invoice == "Yes") {
	// 					//LODGE
	// 					if ($lodging->lodgingTaxInvoice && (($lodging->lodgingTaxInvoice->cgst != '0.00' && $lodging->lodgingTaxInvoice->sgst != '0.00') || ($lodging->lodgingTaxInvoice->igst != '0.00'))) {
	// 						if ($lodging->lodgingTaxInvoice->cgst > 0 && $lodging->lodgingTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->lodgingTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->lodgingTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->lodgingTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->lodgingTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->lodgingTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 					//DRY WASH
	// 					if ($lodging->drywashTaxInvoice && (($lodging->drywashTaxInvoice->cgst != '0.00' && $lodging->drywashTaxInvoice->sgst != '0.00') || ($lodging->drywashTaxInvoice->igst != '0.00'))) {

	// 						if ($lodging->drywashTaxInvoice->cgst > 0 && $lodging->drywashTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->drywashTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->drywashTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->drywashTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->drywashTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->drywashTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 					//BOARDING
	// 					if ($lodging->boardingTaxInvoice && (($lodging->boardingTaxInvoice->cgst != '0.00' && $lodging->boardingTaxInvoice->sgst != '0.00') || ($lodging->boardingTaxInvoice->igst != '0.00'))) {
	// 						if ($lodging->boardingTaxInvoice->cgst > 0 && $lodging->boardingTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->boardingTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->boardingTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->boardingTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->boardingTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->boardingTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 					//OTHERS
	// 					if ($lodging->othersTaxInvoice && (($lodging->othersTaxInvoice->cgst != '0.00' && $lodging->othersTaxInvoice->sgst != '0.00') || ($lodging->othersTaxInvoice->igst != '0.00'))) {
	// 						if ($lodging->othersTaxInvoice->cgst > 0 && $lodging->othersTaxInvoice->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->othersTaxInvoice->cgst);
	// 							$sgstAmount += floatval($lodging->othersTaxInvoice->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->othersTaxInvoice->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->othersTaxInvoice->igst);
	// 							$igstPercentage += floatval($lodging->othersTaxInvoice->tax_percentage);
	// 						}
	// 					}

	// 				} else {
	// 					//SINGLE
	// 					if ($lodging && (($lodging->cgst != '0.00' && $lodging->sgst != '0.00') || ($lodging->igst != '0.00'))) {
	// 						if ($lodging->cgst > 0 && $lodging->sgst > 0) {
	// 							$cgstAmount += floatval($lodging->cgst);
	// 							$sgstAmount += floatval($lodging->sgst);
	// 							$cgstSgstPercentage += floatval($lodging->tax_percentage);
	// 						} else {
	// 							$igstAmount += floatval($lodging->igst);
	// 							$igstPercentage += floatval($lodging->tax_percentage);
	// 						}
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}

	// 	//GST TAX CLASSIFICATION
	// 	$taxClassification = '';
	// 	if ($cgstAmount > 0 && $sgstAmount > 0) {
	// 		$taxClassification .= 'CGST+SGST REC ' . (round($cgstSgstPercentage));
	// 	}
	// 	if ($igstAmount > 0) {
	// 		$taxClassification .= 'IGST REC ' . (round($igstPercentage));
	// 	}

	// 	$taxAmount = $cgstAmount + $sgstAmount + $igstAmount;

	// 	//ROUND OFF
	// 	$employeeLodgingRoundoff = floatval($employeeTrip->lodgings()->sum('round_off'));
	// 	$roundOffAmt = round($employeeTrip->totalAmount) - $employeeTrip->totalAmount;
	// 	$employeeLodgingRoundoff += floatval($roundOffAmt);

	// 	DB::table('oracle_ap_invoice_exports')->insert([
	// 		'company_id' => $companyId,
	// 		'business_unit' => $businessUnitName,
	// 		'invoice_source' => $invoiceSource,
	// 		'invoice_number' => $invoiceNumber,
	// 		'invoice_amount' => $invoiceAmount,
	// 		'invoice_date' => $invoiceDate,
	// 		'pre_payment_invoice_number' => $prePaymentNumber,
	// 		'pre_payment_invoice_date' => $prePaymentDate,
	// 		'pre_payment_amount' => $prePaymentAmount,
	// 		'supplier_number' => $supplierNumber,
	// 		'supplier_site_name' => $supplierSiteName,
	// 		'invoice_type' => $invoiceType,
	// 		'description' => $description,
	// 		'amount' => $amount,
	// 		'tax_classification' => $taxClassification,
	// 		'cgst' => $cgstAmount,
	// 		'sgst' => $sgstAmount,
	// 		'igst' => $igstAmount,
	// 		'outlet' => $outletCode,
	// 		'round_off_amount' => $employeeLodgingRoundoff,
	// 		'tax_amount' => $taxAmount,
	// 		'accounting_class' => $accountingClass,
	// 		'company' => $company,
	// 		'lob' => $lob,
	// 		'location' => $location,
	// 		'department' => $department,
	// 		'natural_account' => $naturalAccount,
	// 		'created_at' => Carbon::now(),
	// 	]);

	// 	//IF ADVANCE RECEIVED
	// 	if ($employeeTrip->advance_received && $employeeTrip->advance_received > 0) {
	// 		//COMPANY TO EMPLOYEE
	// 		if ($employeeClaim->amount_to_pay == 1) {
	// 			if ($employeeClaim->balance_amount && $employeeClaim->balance_amount != '0.00') {
	// 				//ROUND OFF
	// 				$amountDiff = 0;
	// 				if (!empty($employeeClaim->balance_amount)) {
	// 					$amountDiff = number_format((round($employeeClaim->balance_amount) - $employeeClaim->balance_amount), 2);
	// 				}

	// 				DB::table('oracle_ap_invoice_exports')->insert([
	// 					'company_id' => $companyId,
	// 					'business_unit' => $businessUnitName,
	// 					'invoice_source' => $prePaymentType,
	// 					'invoice_number' => $invoiceNumber,
	// 					'invoice_date' => $invoiceDate,
	// 					'pre_payment_invoice_number' => $prePaymentNumber,
	// 					'pre_payment_invoice_date' => $prePaymentDate,
	// 					'pre_payment_amount' => $prePaymentAmount,
	// 					'supplier_number' => $supplierNumber,
	// 					'supplier_site_name' => $supplierSiteName,
	// 					'invoice_type' => $invoiceType,
	// 					'description' => $description,
	// 					'amount' => $employeeClaim->balance_amount,
	// 					'outlet' => $outletCode,
	// 					'round_off_amount' => $amountDiff,
	// 					'accounting_class' => 'Payable',
	// 					'company' => $company,
	// 					'lob' => $lob,
	// 					'location' => $location,
	// 					'department' => $department,
	// 					'natural_account' => $naturalAccount,
	// 					'created_at' => Carbon::now(),
	// 				]);
	// 			}
	// 		}

	// 		//EMPLOYEE TO COMPANY
	// 		// if ($employeeClaim->amount_to_pay == 2) {
	// 		// 	if ($employeeClaim->balance_amount && $employeeClaim->balance_amount != '0.00') {
	// 		// 		//ROUND OFF
	// 		// 		$amountDiff = 0;
	// 		// 		if (!empty($employeeClaim->balance_amount)) {
	// 		// 			$amountDiff = number_format((round($employeeClaim->balance_amount) - $employeeClaim->balance_amount), 2);
	// 		// 		}

	// 		// 		DB::table('oracle_ar_invoice_exports')->insert([
	// 		// 			'company_id' => $companyId,
	// 		// 			'business_unit' => $businessUnitName,
	// 		// 			'transaction_class' => $prePaymentClass,
	// 		// 			'transaction_batch_source_name' => $prePaymentBatch,
	// 		// 			'transaction_type_name' => $prePaymentType,
	// 		// 			'transaction_number' => $invoiceNumber,
	// 		// 			// 'pre_payment_invoice_number' => $tripNumber,
	// 		// 			// 'invoice_amount' => $invoiceAmount,
	// 		// 			'transaction_date' => $invoiceDate,
	// 		// 			// 'pre_payment_invoice_date' => $tripDate,
	// 		// 			'customer_account_number' => $customerCode,
	// 		// 			'bill_to_customer_site_number' => $customerSiteNumber,
	// 		// 			'credit_outlet' => $outletCode,
	// 		// 			'description' => $description,
	// 		// 			'quantity' => 1,
	// 		// 			'unit_price' => $employeeClaim->balance_amount,
	// 		// 			'amount' => $employeeClaim->balance_amount,
	// 		// 			// 'tax_classification' => $taxClassification,
	// 		// 			// 'cgst' => $cgstAmount,
	// 		// 			// 'sgst' => $sgstAmount,
	// 		// 			// 'igst' => $igstAmount,
	// 		// 			'round_off_amount' => $amountDiff,
	// 		// 			'accounting_class' => 'REV',
	// 		// 			'company' => $companyCode,
	// 		// 			'lob' => $lob,
	// 		// 			'location' => $location,
	// 		// 			'cost_centre' => $costCentre,
	// 		// 			'natural_account' => $naturalAccount,
	// 		// 			'created_at' => Carbon::now(),
	// 		// 		]);
	// 		// 	}
	// 		// }
	// 	}

	// 	$res['success'] = true;
	// 	DB::setDefaultConnection('mysql');
	// 	return $res;
	// }

	//OLD FUNCTION COMMENTED ON 12-05-2023
	// public function generateInvoiceApOracleAxapta() {
	// 	$res = [];
	// 	$res['success'] = false;
	// 	$res['errors'] = [];

	// 	$employeeTrip = $this;
	// 	$companyId = $employeeTrip->company_id;
	// 	$companyBusinessUnit = isset($employeeTrip->company->oem_business_unit->name) ? $employeeTrip->company->oem_business_unit->name : null;
	// 	// $companyCode = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;

	// 	$transactionDetail = $employeeTrip->company ? $employeeTrip->company->invoiceTransaction() : null;
	// 	$invoiceSource = 'Invoice';
	// 	if (!empty($transactionDetail)) {
	// 		$invoiceSource = $transactionDetail->type ? $transactionDetail->type : $invoiceSource;
	// 	}

	// 	$employeeClaim = EmployeeClaim::select([
	// 		'id',
	// 		'number',
	// 		'total_amount',
	// 		'boarding_total',
	// 		'local_travel_total',
	// 		'amount_to_pay',
	// 		'balance_amount',
	// 		'created_at',
	// 	])
	// 		->where('trip_id', $employeeTrip->id)
	// 		->first();

	// 	$invoiceAmount = null;
	// 	$invoiceDate = null;
	// 	$invoiceNumber = null;
	// 	$prePaymentNumber = null;
	// 	// $prePaymentDate = null;
	// 	$prePaymentAmount = null;
	// 	if ($employeeClaim) {
	// 		$invoiceAmount = round($employeeClaim->total_amount);
	// 		$invoiceDate = $employeeClaim->created_at ? date("Y-m-d", strtotime($employeeClaim->created_at)) : null;
	// 		$invoiceNumber = $employeeClaim->number;

	// 		if ($employeeTrip->advance_received && $employeeTrip->advance_received > 0) {
	// 			$prePaymentNumber = $employeeTrip->number;
	// 			// $prePaymentDate = $employeeTrip->created_at ? date("Y-m-d", strtotime($employeeTrip->created_at)) : null;
	// 			$prePaymentAmount = $employeeTrip->advance_received;
	// 		}
	// 	}

	// 	$businessUnitName = $companyBusinessUnit;
	// 	$employeeData = $employeeTrip->employee;
	// 	$customerCode = $employeeData ? $employeeData->code : null;
	// 	$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
	// 	$invoiceType = 'Standard';
	// 	$description = '';
	// 	if (!empty($employeeData->code)) {
	// 		$description .= $employeeData->code;
	// 	}
	// 	if (!empty($employeeData->user->name)) {
	// 		$description .= ' - ' . ($employeeData->user->name);
	// 	}
	// 	if (!empty($employeeTrip->purpose->name)) {
	// 		$description .= ' - ' . ($employeeTrip->purpose->name);
	// 	}

	// 	//VISITS
	// 	$employeeTransportValue = 0;
	// 	if ($employeeTrip->selfVisits->isNotEmpty()) {
	// 		foreach ($employeeTrip->selfVisits as $selfVisit) {
	// 			if (!empty($selfVisit->booking)) {
	// 				$employeeTransportValue += floatval($selfVisit->booking->invoice_amount);
	// 			}
	// 		}
	// 	}

	// 	//BOARDING
	// 	$employeeBoardingValue = 0;
	// 	if ($employeeTrip->boardings->isNotEmpty()) {
	// 		$employeeBoardingValue = floatval($employeeClaim->boarding_total);
	// 	}

	// 	//LOCAL TRAVELS
	// 	$employeeLocalTravelValue = 0;
	// 	if ($employeeTrip->localTravels->isNotEmpty()) {
	// 		$employeeLocalTravelValue = floatval($employeeClaim->local_travel_total);
	// 	}

	// 	$outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
	// 	$customerSiteNumber = $outletCode;
	// 	// $accountingClass = 'Payable';
	// 	$accountingClass = 'Purchase/Expense';
	// 	$company = $employeeTrip->company ? $employeeTrip->company->oracle_code : '';

	// 	$sbu = $employeeData->Sbu;
	// 	$lob = $department = null;
	// 	if ($sbu) {
	// 		$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
	// 		$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
	// 	}
	// 	$location = $outletCode;
	// 	$naturalAccount = Config::where('id', 3861)->first()->name;
	// 	$empToCompanyNaturalAccount = Config::where('id', 3921)->first()->name;
	// 	$supplierSiteName = $outletCode;

	// 	$roundOffTransaction = OtherTypeTransactionDetail::apRoundOffTransaction();
	// 	$bpas_portal = Portal::select([
	// 		'db_host_name',
	// 		'db_port_number',
	// 		'db_name',
	// 		'db_user_name',
	// 		'db_password',
	// 	])
	// 		->where('id', 1)
	// 		->first();
	// 	DB::setDefaultConnection('dynamic');
	// 	$db_host_name = dataBaseConfig::set('database.connections.dynamic.host', $bpas_portal->db_host_name);
	// 	$db_port_number = dataBaseConfig::set('database.connections.dynamic.port', $bpas_portal->db_port_number);
	// 	$db_port_driver = dataBaseConfig::set('database.connections.dynamic.driver', "mysql");
	// 	$db_name = dataBaseConfig::set('database.connections.dynamic.database', $bpas_portal->db_name);
	// 	$db_username = dataBaseConfig::set('database.connections.dynamic.username', $bpas_portal->db_user_name);
	// 	$db_username = dataBaseConfig::set('database.connections.dynamic.password', $bpas_portal->db_password);
	// 	DB::purge('dynamic');
	// 	DB::reconnect('dynamic');

	// 	$apInvoiceExports = DB::table('oracle_ap_invoice_exports')->where([
	// 		'invoice_number' => $invoiceNumber,
	// 		'business_unit' => $businessUnitName,
	// 		'invoice_source' => $invoiceSource,
	// 	])->get();
	// 	if (count($apInvoiceExports) > 0) {
	// 		$res['errors'] = ['Already exported to oracle table'];
	// 		return $res;
	// 	}

	// 	//LODGING
	// 	$lodgingCgstSgstTaxableAmount = 0;
	// 	$lodgingCgstAmount = 0;
	// 	$lodgingSgstAmount = 0;
	// 	$lodgingCgstSgstPercentage = 0;

	// 	$lodgingIgstTaxableAmount = 0;
	// 	$lodgingIgstAmount = 0;
	// 	$lodgingIgstPercentage = 0;
	// 	$lodgingWithoutGstValue = 0;

	// 	if ($employeeTrip->lodgings->isNotEmpty()) {
	// 		foreach ($employeeTrip->lodgings as $lodging) {
	// 			//LODGE STAY
	// 			if (($lodging->cgst != '0.00' && $lodging->sgst != '0.00') || ($lodging->igst != '0.00')) {
	// 				if ($lodging->cgst > 0 && $lodging->sgst > 0) {
	// 					$lodgingCgstSgstTaxableAmount += floatval($lodging->amount);
	// 					$lodgingCgstAmount += floatval($lodging->cgst);
	// 					$lodgingSgstAmount += floatval($lodging->sgst);
	// 					$lodgingCgstSgstPercentage += floatval($lodging->tax_percentage);
	// 				} else {
	// 					$lodgingIgstTaxableAmount += floatval($lodging->amount);
	// 					$lodgingIgstAmount += floatval($lodging->igst);
	// 					$lodgingIgstPercentage += floatval($lodging->tax_percentage);
	// 				}
	// 			} else {
	// 				$lodgingWithoutGstValue += floatval($lodging->amount);
	// 			}
	// 		}
	// 	}

	// 	$withoutTaxAmount = floatval($employeeTransportValue + $employeeBoardingValue + $employeeLocalTravelValue + $lodgingWithoutGstValue);

	// 	//ROUND OFF
	// 	$employeeLodgingRoundoff = floatval($employeeTrip->lodgings()->sum('round_off'));
	// 	$roundOffAmt = round($employeeTrip->totalAmount) - $employeeTrip->totalAmount;
	// 	$employeeLodgingRoundoff += floatval($roundOffAmt);

	// 	//TRANSPORT , BOARDING, LOCAL TRAVEL, LODGING-NON GST ENTRY
	// 	// $this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, $invoiceAmount, $invoiceDate, $prePaymentNumber, $prePaymentDate, $prePaymentAmount, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $withoutTaxAmount, null, null, null, null, $employeeLodgingRoundoff, null, null, $accountingClass, $company, $lob, $location, $department, $naturalAccount);
	// 	$apInvoiceId = $this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, $invoiceAmount, $invoiceDate, $prePaymentNumber, null, $prePaymentAmount, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $withoutTaxAmount, null, null, null, null, null, null, null, $accountingClass, $company, $lob, $location, $department, $naturalAccount);

	// 	//LODGING-GST ENTRY
	// 	if ($lodgingCgstSgstTaxableAmount && $lodgingCgstSgstTaxableAmount > 0) {
	// 		$taxClassification = 'CGST+SGST REC ' . (round($lodgingCgstSgstPercentage));
	// 		$taxAmount = $lodgingCgstAmount + $lodgingSgstAmount;

	// 		$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $lodgingCgstSgstTaxableAmount, $taxClassification, $lodgingCgstAmount, $lodgingSgstAmount, null, null, null, $taxAmount, $accountingClass, $company, $lob, $location, $department, $naturalAccount);
	// 	}

	// 	if ($lodgingIgstTaxableAmount && $lodgingIgstTaxableAmount > 0) {
	// 		$taxClassification = 'IGST REC ' . (round($lodgingIgstPercentage));
	// 		$taxAmount = $lodgingIgstAmount;

	// 		$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $lodgingIgstTaxableAmount, $taxClassification, null, null, $lodgingIgstAmount, null, null, $taxAmount, $accountingClass, $company, $lob, $location, $department, $naturalAccount);
	// 	}

	// 	//ROUND OFF ENTRY
	// 	if ($employeeLodgingRoundoff && $employeeLodgingRoundoff != '0.00') {
	// 		$roundOffDescription = null;
	// 		$roundOffAccountingClass = null;
	// 		$roundOffNaturalAccount = null;
	// 		if ($roundOffTransaction) {
	// 			$roundOffDescription = $roundOffTransaction->name;
	// 			$roundOffAccountingClass = $roundOffTransaction->accounting_class;
	// 			$roundOffNaturalAccount = $roundOffTransaction->natural_account;
	// 		}

	// 		$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $roundOffDescription, $outletCode, $employeeLodgingRoundoff, null, null, null, null, null, null, null, $roundOffAccountingClass, $company, $lob, $location, $department, $roundOffNaturalAccount);
	// 	}

	// 	//IF ADVANCE RECEIVED
	// 	if ($employeeTrip->advance_received && $employeeTrip->advance_received > 0) {
	// 		if ($employeeClaim->balance_amount && $employeeClaim->balance_amount != '0.00') {
	// 			//EMPLOYEE TO COMPANY
	// 			if ($employeeClaim->amount_to_pay == 2) {
	// 				$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $employeeClaim->balance_amount, null, null, null, null, null, null, null, $accountingClass, $company, $lob, $location, $department, $empToCompanyNaturalAccount);
	// 			}

	// 			// //PRE PAYMENT DETAILS SAVE
	// 			// $prePaymentDetails = DB::table('oracle_pre_payment_invoice_details')->where([
	// 			// 	'ap_invoice_id' => $apInvoiceId,
	// 			// ])->get();
	// 			// if (count($prePaymentDetails) > 0) {
	// 			// 	$res['errors'] = ['Pre payment invoice already exported to oracle table'];
	// 			// 	return $res;
	// 			// }
	// 			// $this->savePrePaymentInvoice($apInvoiceId, $businessUnitName, $supplierNumber, $invoiceNumber, $prePaymentNumber, $prePaymentAmount);
	// 		}

	// 		//PRE PAYMENT DETAILS SAVE
	// 		$prePaymentDetails = DB::table('oracle_pre_payment_invoice_details')->where([
	// 			'ap_invoice_id' => $apInvoiceId,
	// 		])->get();
	// 		if (count($prePaymentDetails) > 0) {
	// 			$res['errors'] = ['Pre payment invoice already exported to oracle table'];
	// 			return $res;
	// 		}
	// 		$this->savePrePaymentInvoice($apInvoiceId, $businessUnitName, $supplierNumber, $invoiceNumber, $prePaymentNumber, $prePaymentAmount);
	// 	}

	// 	$res['success'] = true;
	// 	DB::setDefaultConnection('mysql');
	// 	return $res;
	// }

	public function generateInvoiceApOracleAxapta() {
		$res = [];
		$res['success'] = false;
		$res['errors'] = [];

		$employeeTrip = $this;
		$companyId = $employeeTrip->company_id;
		// $companyBusinessUnit = isset($employeeTrip->company->oem_business_unit->name) ? $employeeTrip->company->oem_business_unit->name : null;

		// $transactionDetail = $employeeTrip->company ? $employeeTrip->company->invoiceTransaction() : null;

		if(!empty($employeeTrip->employee->department) && $employeeTrip->employee->department->business_id == 2){
			$transactionDetail = $employeeTrip->company ? $employeeTrip->company->oeslInvoiceTransaction() : null;
			$claimRefundDetail = $employeeTrip->company ? $employeeTrip->company->oeslClaimRefundInvoiceTransaction() : null;
			$companyBusinessUnit = isset($employeeTrip->company->oes_business_unit->name) ? $employeeTrip->company->oes_business_unit->name : null;
			$company  = isset($employeeTrip->company->oes_business_unit->code) ? $employeeTrip->company->oes_business_unit->code : null;

		}else if(!empty($employeeTrip->employee->department) && $employeeTrip->employee->department->business_id == 3){
			$transactionDetail = $employeeTrip->company ? $employeeTrip->company->hondaInvoiceTransaction() : null;
			$claimRefundDetail = $employeeTrip->company ? $employeeTrip->company->hondaClaimRefundInvoiceTransaction() : null;
			$companyBusinessUnit = isset($employeeTrip->company->oem_business_unit->name) ? $employeeTrip->company->oem_business_unit->name : null;
			$company  = isset($employeeTrip->company->oem_business_unit->code) ? $employeeTrip->company->oem_business_unit->code : null;

		}else if(!empty($employeeTrip->employee->department) && $employeeTrip->employee->department->business_id == 8){
			$transactionDetail = $employeeTrip->company ? $employeeTrip->company->investmentInvoiceTransaction() : null;
			$claimRefundDetail = $employeeTrip->company ? $employeeTrip->company->investmentClaimRefundInvoiceTransaction() : null;
			$companyBusinessUnit = isset($employeeTrip->company->investment_business_unit->name) ? $employeeTrip->company->investment_business_unit->name : null;
			$company  = isset($employeeTrip->company->investment_business_unit->code) ? $employeeTrip->company->investment_business_unit->code : null;
		}else{
			$transactionDetail = $employeeTrip->company ? $employeeTrip->company->invoiceTransaction() : null;
			$claimRefundDetail = $employeeTrip->company ? $employeeTrip->company->claimRefundInvoiceTransaction() : null;
			$companyBusinessUnit = isset($employeeTrip->company->oem_business_unit->name) ? $employeeTrip->company->oem_business_unit->name : null;
			$company  = isset($employeeTrip->company->oem_business_unit->code) ? $employeeTrip->company->oem_business_unit->code : null;
		}

		// $invoiceSource = 'Invoice';
		$invoiceSource = 'Travelex';
		$documentType = 'Invoice';
		if (!empty($transactionDetail)) {
			// $invoiceSource = $transactionDetail->type ? $transactionDetail->type : $invoiceSource;
			$invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
			$documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
		}

		$claimRefundInvoiceSource = 'Travelex';
		$claimRefundDocumentType = 'Invoice';
		if (!empty($claimRefundDetail)) {
			$claimRefundInvoiceSource = $claimRefundDetail->batch ? $claimRefundDetail->batch : $claimRefundInvoiceSource;
			$claimRefundDocumentType = $claimRefundDetail->type ? $claimRefundDetail->type : $claimRefundDocumentType;
		}

		$employeeClaim = EmployeeClaim::select([
			'id',
			'number',
			'total_amount',
			'boarding_total',
			'local_travel_total',
			'amount_to_pay',
			'balance_amount',
			'created_at',
			'updated_at',
		])
			->where('trip_id', $employeeTrip->id)
			->first();

		$invoiceAmount = null;
		$invoiceDate = null;
		$invoiceNumber = null;
		$prePaymentNumber = null;
		$prePaymentAmount = null;
		if ($employeeClaim) {
			$invoiceAmount = round($employeeClaim->total_amount);
			// $invoiceDate = $employeeClaim->created_at ? date("Y-m-d", strtotime($employeeClaim->created_at)) : null;
			$invoiceDate = $employeeClaim->updated_at ? date("Y-m-d", strtotime($employeeClaim->updated_at)) : null;
			$invoiceNumber = $employeeClaim->number;

			if ($employeeTrip->advance_received && $employeeTrip->advance_received > 0) {
				$prePaymentNumber = $employeeTrip->number;
				$prePaymentAmount = $employeeTrip->advance_received;
			}
		}

		$businessUnitName = $companyBusinessUnit;
		$employeeData = $employeeTrip->employee;
		$customerCode = $employeeData ? $employeeData->code : null;
		$supplierNumber = $employeeData ? 'EMP_' . ($employeeData->code) : null;
		$invoiceType = 'Standard';
		$invoiceDescription = '';
		if (!empty($employeeData->code)) {
			$invoiceDescription .= $employeeData->code;
		}
		if (!empty($employeeData->user->name)) {
			$invoiceDescription .= ',' . ($employeeData->user->name);
		}
		if (!empty($employeeTrip->purpose->name)) {
			$invoiceDescription .= ',' . ($employeeTrip->purpose->name);
		}

		$invoiceDescription .= ',Travel Date : ' . date('Y-m-d', strtotime($employeeTrip->start_date)) .' to '. date('Y-m-d', strtotime($employeeTrip->end_date));

		$tripLocations = Visit::select([
			DB::raw("CASE 
				WHEN tocity.type_id = 4111 and visits.other_city is not null 
				THEN visits.other_city 
				WHEN tocity.type_id = 4111 and visits.other_city is null 
				THEN tocity.name
				ELSE tocity.name 
				END as location"),
		])
			->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
			->whereNotIn('visits.status_id', [3062]) //Cancelled
			->where('visits.trip_id', $employeeTrip->id)
			->orderBy('visits.id')
			->get()
			->implode('location', ',');
		if($tripLocations){
			$invoiceDescription .= ',Travel Place : ' . ($tripLocations);
		}

		$tripClaimApprovalLog = ApprovalLog::select([
			'id',
			DB::raw('DATE_FORMAT(approved_at,"%Y-%m-%d") as approved_date'),
		])
			->where('type_id', 3581) //Outstation Trip
			->where('approval_type_id', 3601) //Outstation Trip Claim - Manager Approved
			->where('entity_id', $employeeTrip->id)
			->first();
		$claimManagerApprovedDate = null;
		if($tripClaimApprovalLog){
			$claimManagerApprovedDate = $tripClaimApprovalLog->approved_date;
		}

		//VISITS
		$employeeTransportValue = 0;
		if ($employeeTrip->selfVisits->isNotEmpty()) {
			foreach ($employeeTrip->selfVisits as $selfVisit) {
				if (!empty($selfVisit->booking)) {
					// $employeeTransportValue += floatval($selfVisit->booking->invoice_amount);
					if($selfVisit->booking->invoice_amount && $selfVisit->booking->invoice_amount != '0.00'){
						$employeeTransportValue += floatval($selfVisit->booking->invoice_amount);
					}else{
						$employeeTransportValue += (floatval($selfVisit->booking->amount) + floatval($selfVisit->booking->other_charges) + floatval($selfVisit->booking->round_off));
					}
				}
			}
		}

		//BOARDING
		$employeeBoardingValue = 0;
		if ($employeeTrip->boardings->isNotEmpty()) {
			$employeeBoardingValue = floatval($employeeClaim->boarding_total);
		}

		//LOCAL TRAVELS
		$employeeLocalTravelValue = 0;
		if ($employeeTrip->localTravels->isNotEmpty()) {
			$employeeLocalTravelValue = floatval($employeeClaim->local_travel_total);
		}

		// $outletCode = $employeeData->outlet ? $employeeData->outlet->oracle_code_l2 : null;
		$outletCode = $this->branch ? $this->branch->oracle_code_l2 : null;
		$customerSiteNumber = $outletCode;
		// $accountingClass = 'Payable';
		$accountingClass = 'Purchase/Expense';
		// $company = $employeeTrip->company ? $employeeTrip->company->oracle_code : '';
		// $company  = isset($this->company->oem_business_unit->code) ? $this->company->oem_business_unit->code : null;;

		$sbu = $employeeData->Sbu;
		$lob = $department = null;
		if ($sbu) {
			$lob = $sbu->oracle_code ? $sbu->oracle_code : null;
			$department = $sbu->oracle_cost_centre ? $sbu->oracle_cost_centre : null;
		}
		$location = $outletCode;
		$naturalAccount = Config::where('id', 3861)->first()->name;
		$empToCompanyNaturalAccount = Config::where('id', 3921)->first()->name;
		$supplierSiteName = $outletCode;

		$roundOffTransaction = OtherTypeTransactionDetail::apRoundOffTransaction();
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

		//LODGING
		// $lodgingCgstSgstTaxableAmount = 0;
		// $lodgingCgstAmount = 0;
		// $lodgingSgstAmount = 0;
		// $lodgingCgstSgstPercentage = 0;

		// $lodgingIgstTaxableAmount = 0;
		// $lodgingIgstAmount = 0;
		// $lodgingIgstPercentage = 0;
		// $gstLodging = false;
		$singleInvoiceLodgeWithoutGstValue = 0;
		$multiTaxInvoiceLodgeWithoutGstValue = 0;
		$multiTaxInvoiceOtherAmount = 0;
		$multiTaxInvoiceDiscountAmount = 0;

		if ($employeeTrip->lodgings->isNotEmpty()) {
			foreach ($employeeTrip->lodgings as $lodging) {
				//LODGE STAY
				if ($lodging->has_multiple_tax_invoice == "Yes") {
					//HAS MULTIPLE TAX INVOICE
					if (($lodging->cgst > 0 && $lodging->sgst > 0) || ($lodging->igst > 0)) {
						if (!empty($lodging->othersTaxInvoice)) {
							$multiTaxInvoiceOtherAmount += floatval($lodging->othersTaxInvoice->without_tax_amount);
						}
						if (!empty($lodging->discountTaxInvoice)) {
							$multiTaxInvoiceDiscountAmount += floatval($lodging->discountTaxInvoice->without_tax_amount);
						}
					} else {
						$multiTaxInvoiceLodgeWithoutGstValue += floatval($lodging->amount);
					}
				} else {
					//SINGLE
					if (($lodging->cgst > 0 && $lodging->sgst > 0) || ($lodging->igst > 0)) {
						// $gstLodging = true;

						// //HAS MULTIPLE TAX INVOICE
						// if ($lodging->has_multiple_tax_invoice == "Yes" && $lodging->othersTaxInvoice) {
						// 	$multiTaxInvoiceOtherAmount += floatval($lodging->othersTaxInvoice->without_tax_amount);
						// }
					} else {
						$singleInvoiceLodgeWithoutGstValue += floatval($lodging->amount);
					}
				}

				// if (($lodging->cgst > 0 && $lodging->sgst > 0) || ($lodging->igst > 0)) {
				// 	// if ($lodging->cgst > 0 && $lodging->sgst > 0) {
				// 	// 	$lodgingCgstSgstTaxableAmount += floatval($lodging->amount);
				// 	// 	$lodgingCgstAmount += floatval($lodging->cgst);
				// 	// 	$lodgingSgstAmount += floatval($lodging->sgst);
				// 	// 	$lodgingCgstSgstPercentage += floatval($lodging->tax_percentage);
				// 	// } else {
				// 	// 	$lodgingIgstTaxableAmount += floatval($lodging->amount);
				// 	// 	$lodgingIgstAmount += floatval($lodging->igst);
				// 	// 	$lodgingIgstPercentage += floatval($lodging->tax_percentage);
				// 	// }
				// 	$gstLodging = true;

				// 	//HAS MULTIPLE TAX INVOICE
				// 	if ($lodging->has_multiple_tax_invoice == "Yes" && $lodging->othersTaxInvoice) {
				// 		$multiTaxInvoiceOtherAmount += floatval($lodging->othersTaxInvoice->without_tax_amount);
				// 	}
				// } else {
				// 	$lodgingWithoutGstValue += floatval($lodging->amount);
				// }
			}
		}

		$withoutTaxAmount = ($employeeTransportValue + $employeeBoardingValue + $employeeLocalTravelValue + $singleInvoiceLodgeWithoutGstValue + $multiTaxInvoiceLodgeWithoutGstValue + $multiTaxInvoiceOtherAmount) - $multiTaxInvoiceDiscountAmount;

		//ROUND OFF
		$employeeLodgingRoundoff = floatval($employeeTrip->lodgings()->sum('round_off'));
		$roundOffAmt = round($employeeTrip->totalAmount) - $employeeTrip->totalAmount;
		$employeeLodgingRoundoff += floatval($roundOffAmt);

		//TRANSPORT , BOARDING, LOCAL TRAVEL, LODGING-NON GST ENTRY
		// $this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, $invoiceAmount, $invoiceDate, $prePaymentNumber, $prePaymentDate, $prePaymentAmount, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $withoutTaxAmount, null, null, null, null, $employeeLodgingRoundoff, null, null, $accountingClass, $company, $lob, $location, $department, $naturalAccount);
		$apInvoiceId = $this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, $invoiceAmount, $claimManagerApprovedDate, $prePaymentNumber, null, $prePaymentAmount, $supplierNumber, $supplierSiteName, $invoiceType, $invoiceDescription, $outletCode, $withoutTaxAmount, null, null, null, null, null, null, null, $accountingClass, $company, $lob, $location, $department, $naturalAccount , $documentType , $claimManagerApprovedDate);

		// //LODGING-GST ENTRY
		// if ($lodgingCgstSgstTaxableAmount && $lodgingCgstSgstTaxableAmount > 0) {
		// 	$taxClassification = 'CGST+SGST REC ' . (round($lodgingCgstSgstPercentage));
		// 	$taxAmount = $lodgingCgstAmount + $lodgingSgstAmount;

		// 	$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $lodgingCgstSgstTaxableAmount, $taxClassification, $lodgingCgstAmount, $lodgingSgstAmount, null, null, null, $taxAmount, $accountingClass, $company, $lob, $location, $department, $naturalAccount);
		// }

		// if ($lodgingIgstTaxableAmount && $lodgingIgstTaxableAmount > 0) {
		// 	$taxClassification = 'IGST REC ' . (round($lodgingIgstPercentage));
		// 	$taxAmount = $lodgingIgstAmount;

		// 	$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $invoiceDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $description, $outletCode, $lodgingIgstTaxableAmount, $taxClassification, null, null, $lodgingIgstAmount, null, null, $taxAmount, $accountingClass, $company, $lob, $location, $department, $naturalAccount);
		// }

		//GST LODGING
		if ($employeeTrip->lodgings->isNotEmpty()) {
			foreach ($employeeTrip->lodgings as $lodging) {
				if ($lodging->stay_type_id == 3340) {
					//HAS MULTIPLE TAX INVOICE
					if ($lodging->has_multiple_tax_invoice == "Yes") {
						$lodgingTaxInvoice = $lodging->lodgingTaxInvoice;
						if ($lodgingTaxInvoice && (($lodgingTaxInvoice->cgst > 0 && $lodgingTaxInvoice->sgst > 0) || ($lodgingTaxInvoice->igst > 0))) {
							$taxDetailRes = $this->getLodgingTaxDetail($lodgingTaxInvoice->cgst, $lodgingTaxInvoice->sgst, $lodgingTaxInvoice->igst, $lodgingTaxInvoice->tax_percentage);

							$lineDescription = "Lodging";
							if($lodging->reference_number){
								$lineDescription .= "," .$lodging->reference_number;
							}
							if($lodging->invoice_date){
								$lineDescription .= "," .$lodging->invoice_date;
							}

							$lineDescription .= ',Travel Date : ' . date('Y-m-d', strtotime($employeeTrip->start_date)) .' to '. date('Y-m-d', strtotime($employeeTrip->end_date));
							if($tripLocations){
								$lineDescription .= ',Travel Place : ' . ($tripLocations);
							}

							$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $claimManagerApprovedDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $lineDescription , $outletCode, $lodgingTaxInvoice->without_tax_amount, $taxDetailRes['taxClassification'], $lodgingTaxInvoice->cgst, $lodgingTaxInvoice->sgst, $lodgingTaxInvoice->igst, null, null, $taxDetailRes['taxAmount'], $accountingClass, $company, $lob, $location, $department, $naturalAccount ,$documentType , $claimManagerApprovedDate);
						}

						//DRY WASH
						$drywashTaxInvoice = $lodging->drywashTaxInvoice;
						if ($drywashTaxInvoice && (($drywashTaxInvoice->cgst > 0 && $drywashTaxInvoice->sgst > 0) || ($drywashTaxInvoice->igst > 0))) {
							$taxDetailRes = $this->getLodgingTaxDetail($drywashTaxInvoice->cgst, $drywashTaxInvoice->sgst, $drywashTaxInvoice->igst, $drywashTaxInvoice->tax_percentage);

							$lineDescription = "Lodging-DryWash";
							if($lodging->reference_number){
								$lineDescription .= "," .$lodging->reference_number;
							}
							if($lodging->invoice_date){
								$lineDescription .= "," .$lodging->invoice_date;
							}

							$lineDescription .= ',Travel Date : ' . date('Y-m-d', strtotime($employeeTrip->start_date)) .' to '. date('Y-m-d', strtotime($employeeTrip->end_date));
							if($tripLocations){
								$lineDescription .= ',Travel Place : ' . ($tripLocations);
							}

							$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $claimManagerApprovedDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $lineDescription, $outletCode, $drywashTaxInvoice->without_tax_amount, $taxDetailRes['taxClassification'], $drywashTaxInvoice->cgst, $drywashTaxInvoice->sgst, $drywashTaxInvoice->igst, null, null, $taxDetailRes['taxAmount'], $accountingClass, $company, $lob, $location, $department, $naturalAccount, $documentType , $claimManagerApprovedDate);
						}

						//BOARDING
						$boardingTaxInvoice = $lodging->boardingTaxInvoice;
						if ($boardingTaxInvoice && (($boardingTaxInvoice->cgst > 0 && $boardingTaxInvoice->sgst > 0) || ($boardingTaxInvoice->igst > 0))) {
							$taxDetailRes = $this->getLodgingTaxDetail($boardingTaxInvoice->cgst, $boardingTaxInvoice->sgst, $boardingTaxInvoice->igst, $boardingTaxInvoice->tax_percentage);

							$lineDescription = "Lodging-Boarding";
							if($lodging->reference_number){
								$lineDescription .= "," .$lodging->reference_number;
							}
							if($lodging->invoice_date){
								$lineDescription .= "," .$lodging->invoice_date;
							}

							$lineDescription .= ',Travel Date : ' . date('Y-m-d', strtotime($employeeTrip->start_date)) .' to '. date('Y-m-d', strtotime($employeeTrip->end_date));
							if($tripLocations){
								$lineDescription .= ',Travel Place : ' . ($tripLocations);
							}

							$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $claimManagerApprovedDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $lineDescription, $outletCode, $boardingTaxInvoice->without_tax_amount, $taxDetailRes['taxClassification'], $boardingTaxInvoice->cgst, $boardingTaxInvoice->sgst, $boardingTaxInvoice->igst, null, null, $taxDetailRes['taxAmount'], $accountingClass, $company, $lob, $location, $department, $naturalAccount, $documentType , $claimManagerApprovedDate);
						}
					} else {
						//SINGLE
						if (($lodging->cgst > 0 && $lodging->sgst > 0) || ($lodging->igst > 0)) {
							$taxDetailRes = $this->getLodgingTaxDetail($lodging->cgst, $lodging->sgst, $lodging->igst, $lodging->tax_percentage);
							$lineDescription = "Lodging";
							if($lodging->reference_number){
								$lineDescription .= "," .$lodging->reference_number;
							}
							if($lodging->invoice_date){
								$lineDescription .= "," .$lodging->invoice_date;
							}

							$lineDescription .= ',Travel Date : ' . date('Y-m-d', strtotime($employeeTrip->start_date)) .' to '. date('Y-m-d', strtotime($employeeTrip->end_date));
							if($tripLocations){
								$lineDescription .= ',Travel Place : ' . ($tripLocations);
							}

							$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $claimManagerApprovedDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $lineDescription, $outletCode, $lodging->amount, $taxDetailRes['taxClassification'], $lodging->cgst, $lodging->sgst, $lodging->igst, null, null, $taxDetailRes['taxAmount'], $accountingClass, $company, $lob, $location, $department, $naturalAccount, $documentType , $claimManagerApprovedDate);
						}
					}
				}
			}
		}

		//ROUND OFF ENTRY
		if ($employeeLodgingRoundoff && $employeeLodgingRoundoff != '0.00') {
			$roundOffDescription = null;
			$roundOffAccountingClass = null;
			$roundOffNaturalAccount = null;
			if ($roundOffTransaction) {
				$roundOffDescription = $roundOffTransaction->name;
				$roundOffAccountingClass = $roundOffTransaction->accounting_class;
				$roundOffNaturalAccount = $roundOffTransaction->natural_account;
			}

			$this->saveApOracleExport($companyId, $businessUnitName, $invoiceSource, $invoiceNumber, null, $claimManagerApprovedDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $roundOffDescription, $outletCode, $employeeLodgingRoundoff, null, null, null, null, null, null, null, $roundOffAccountingClass, $company, $lob, $location, $department, $roundOffNaturalAccount, $documentType , $claimManagerApprovedDate);
		}

		//IF ADVANCE RECEIVED
		if ($employeeTrip->advance_received && $employeeTrip->advance_received > 0) {
			if ($employeeClaim->balance_amount && $employeeClaim->balance_amount != '0.00') {
				//EMPLOYEE TO COMPANY
				if ($employeeClaim->amount_to_pay == 2) {
					$this->saveApOracleExport($companyId, $businessUnitName, $claimRefundInvoiceSource, $invoiceNumber, null, $claimManagerApprovedDate, null, null, null, $supplierNumber, $supplierSiteName, $invoiceType, $invoiceDescription, $outletCode, $employeeClaim->balance_amount, null, null, null, null, null, null, null, $accountingClass, $company, $lob, $location, $department, $empToCompanyNaturalAccount, $claimRefundDocumentType , $claimManagerApprovedDate);
				}
			}

			//PRE PAYMENT DETAILS SAVE
			// $prePaymentDetails = DB::table('oracle_pre_payment_invoice_details')->where([
			// 	'ap_invoice_id' => $apInvoiceId,
			// ])->get();
			// if (count($prePaymentDetails) > 0) {
			// 	$res['errors'] = ['Pre payment invoice already exported to oracle table'];
			// 	return $res;
			// }
			// $this->savePrePaymentInvoice($apInvoiceId, $businessUnitName, $supplierNumber, $invoiceNumber, $prePaymentNumber, $prePaymentAmount);
		}

		$res['success'] = true;
		DB::setDefaultConnection('mysql');
		return $res;
	}

	public function saveApOracleExport($companyId, $businessUnit, $invoiceSource, $invoiceNumber, $invoiceAmount, $invoiceDate, $prePaymentInvoiceNumber, $prePaymentInvoiceDate, $prePaymentAmount, $supplierNumber, $supplierSiteName, $invoiceType, $invoiceDescription, $outlet, $amount, $taxClassification, $cgst, $sgst, $igst, $roundOffAmount, $hsnCode, $taxAmount, $accountingClass, $company, $lob, $location, $department, $naturalAccount , $documentType, $accountingDate = null) {
		return $apInvoiceId = DB::table('oracle_ap_invoice_exports')->insertGetId([
			'company_id' => $companyId,
			'business_unit' => $businessUnit,
			'invoice_source' => $invoiceSource,
			'invoice_number' => $invoiceNumber,
			'invoice_amount' => $invoiceAmount,
			'invoice_date' => $invoiceDate,
			'pre_payment_invoice_number' => $prePaymentInvoiceNumber,
			// 'pre_payment_invoice_date' => $prePaymentInvoiceDate,
			'pre_payment_amount' => $prePaymentAmount,
			'supplier_number' => $supplierNumber,
			'supplier_site_name' => $supplierSiteName,
			'invoice_type' => $invoiceType,
			// 'description' => $description,
			'invoice_description' => $invoiceDescription,
			'outlet' => $outlet,
			'amount' => $amount,
			'tax_classification' => $taxClassification,
			'cgst' => $cgst,
			'sgst' => $sgst,
			'igst' => $igst,
			// 'round_off_amount' => $roundOffAmount,
			'hsn_code' => $hsnCode,
			'tax_amount' => $taxAmount,
			'accounting_class' => $accountingClass,
			'company' => $company,
			'lob' => $lob,
			'location' => $location,
			'department' => $department,
			'natural_account' => $naturalAccount,
			'document_type' => $documentType,
			'accounting_date' => $accountingDate,
			'created_at' => Carbon::now(),
		]);
	}

	public function savePrePaymentInvoice($apInvoiceId, $businessUnit, $supplierNumber, $standardInvoiceNumber, $prePaymentInvoiceNumber, $prePaymentAmount) {
		DB::table('oracle_pre_payment_invoice_details')->insert([
			'ap_invoice_id' => $apInvoiceId,
			'business_unit' => $businessUnit,
			'supplier_number' => $supplierNumber,
			'standard_invoice_number' => $standardInvoiceNumber,
			'pre_payment_invoice_number' => $prePaymentInvoiceNumber,
			'pre_payment_amount' => $prePaymentAmount,
			'created_at' => Carbon::now(),
		]);
	}

	public function getLodgingTaxDetail($cgst, $sgst, $igst, $taxPercentage) {
		$taxDetail = [];
		if ($cgst > 0 && $sgst > 0) {
			$taxClassification = 'CGST+SGST REC ' . (round($taxPercentage));
			$taxAmount = $cgst + $sgst;
		} else {
			$taxClassification = 'IGST REC ' . (round($taxPercentage));
			$taxAmount = $igst;
		}

		$taxDetail['taxClassification'] = $taxClassification;
		$taxDetail['taxAmount'] = $taxAmount;
		return $taxDetail;
	}

}
