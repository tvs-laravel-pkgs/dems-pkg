<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\ActivityLog;
use Uitoux\EYatra\Boarding;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\GradeAdvancedEligiblity;
use Uitoux\EYatra\LocalTravel;
use Uitoux\EYatra\Lodging;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Uitoux\EYatra\VisitBooking;
use Yajra\Datatables\Datatables;

class TripClaimController extends Controller {
	public function listEYatraTripClaimList(Request $r) {
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
				'trips.status_id',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				'trips.start_date',
				'trips.end_date',
				// DB::raw('DATE_FORMAT(trips.start_date,"%d/%m/%Y") as start_date'),
				// DB::raw('DATE_FORMAT(trips.end_date,"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				DB::raw('FORMAT(trips.advance_received,2,"en_IN") as advance_received'),
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)
			->whereIn('trips.status_id', [3023, 3025, 3026])
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
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc');

		if (!Entrust::can('view-all-trips')) {
			$trips->where('trips.employee_id', Auth::user()->entity_id);
		}
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				if ($trip->status_id == 3023) {

					return '
				<a href="#!/eyatra/trip/claim/edit/' . $trip->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/trip/claim/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
				} else {
					return '

				<a href="#!/eyatra/trip/claim/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';
				}

			})
			->make(true);
	}

	public function eyatraTripClaimFormData($trip_id) {
		return Trip::getClaimFormData($trip_id);
	}
	public function eyatraTripClaimFilterData() {
		return Trip::getFilterData();
	}

	public function saveEYatraTripClaim(Request $request) {
		// dd(Auth::user()->id);
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

			if (empty($request->trip_id)) {
				return response()->json(['success' => false, 'errors' => ['Trip not found']]);
			}

			//SAVING VISITS
			if ($request->visits) {
				// dd($request->visits);
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
						$visit->save();
						// dd($visit_data['id']);

						//UPDATE VISIT BOOKING STATUS ONLY FOR SELF
						if ($booked_by == 'self') {
							$visit_booking = VisitBooking::firstOrNew(['visit_id' => $visit_data['id']]);
							$visit_booking->visit_id = $visit_data['id'];
							$visit_booking->type_id = 3100;
							$visit_booking->travel_mode_id = $visit_data['travel_mode_id'];
							$visit_booking->reference_number = $visit_data['reference_number'];
							$visit_booking->remarks = $visit_data['remarks'];
							$visit_booking->amount = $visit_data['amount'];
							$visit_booking->tax = $visit_data['tax'];
							$visit_booking->gstin = $visit_data['gstin'];
							$visit_booking->service_charge = '0.00';
							$visit_booking->total = $visit_data['total'];
							$visit_booking->paid_amount = $visit_data['total'];
							$visit_booking->created_by = Auth::user()->id;
							$visit_booking->status_id = 3241; //Claimed
							$visit_booking->save();
						}

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

				DB::commit();
				return response()->json(['success' => true]);
			}

			//SAVING LODGINGS
			if ($request->is_lodging) {

				//REMOVE LODGING
				if (!empty($request->lodgings_removal_id)) {
					$lodgings_removal_id = json_decode($request->lodgings_removal_id, true);
					Lodging::whereIn('id', $lodgings_removal_id)->delete();
				}

				//SAVE
				if ($request->lodgings) {
					// dd($request->lodgings);
					// LODGE STAY DAYS SHOULD NOT EXCEED TOTAL TRIP DAYS
					$lodge_stayed_days = (int) array_sum(array_column($request->lodgings, 'stayed_days'));
					$trip_total_days = (int) $request->trip_total_days;
					if ($lodge_stayed_days > $trip_total_days) {
						return response()->json(['success' => false, 'errors' => ['Total lodging days should be less than total trip days']]);
					}

					foreach ($request->lodgings as $lodging_data) {

						$lodging = Lodging::firstOrNew([
							'id' => $lodging_data['id'],
						]);
						$lodging->fill($lodging_data);
						$lodging->trip_id = $request->trip_id;

						//CONCATENATE DATE & TIME
						$check_in_date = $lodging_data['check_in_date'];
						$check_in_time = $lodging_data['check_in_time'];
						$checkout_date = $lodging_data['checkout_date'];
						$checkout_time = $lodging_data['checkout_time'];
						$lodging->check_in_date = date('Y-m-d H:i:s', strtotime("$check_in_date $check_in_time"));
						$lodging->checkout_date = date('Y-m-d H:i:s', strtotime("$checkout_date $checkout_time"));
						$lodging->created_by = Auth::user()->id;
						$lodging->save();

						//STORE ATTACHMENT
						$item_images = storage_path('app/public/trip/lodgings/attachments/');
						Storage::makeDirectory($item_images, 0777);
						if (!empty($lodging_data['attachments'])) {
							foreach ($lodging_data['attachments'] as $key => $attachement) {
								$name = $attachement->getClientOriginalName();
								$attachement->move(storage_path('app/public/trip/lodgings/attachments/'), $name);
								$attachement_lodge = new Attachment;
								$attachement_lodge->attachment_of_id = 3181;
								$attachement_lodge->attachment_type_id = 3200;
								$attachement_lodge->entity_id = $lodging->id;
								$attachement_lodge->name = $name;
								$attachement_lodge->save();
							}
						}
					}
				}

				//GET SAVED LODGINGS
				$saved_lodgings = Trip::with([
					'lodgings',
					'lodgings.city',
					'lodgings.stateType',
					'lodgings.attachments',
				])->find($request->trip_id);

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

				DB::commit();
				return response()->json(['success' => true, 'saved_lodgings' => $saved_lodgings]);
			}
			//SAVING BOARDINGS
			if ($request->is_boarding) {

				//REMOVE BOARDINGS
				if (!empty($request->boardings_removal_id)) {
					$boardings_removal_id = json_decode($request->boardings_removal_id, true);
					Boarding::whereIn('id', $boardings_removal_id)->delete();
				}

				//SAVE
				if ($request->boardings) {

					//TOTAL BOARDING DAYS SHOULD NOT EXCEED TOTAL TRIP DAYS
					$boarding_days = (int) array_sum(array_column($request->boardings, 'days'));
					$trip_total_days = (int) $request->trip_total_days;
					if ($boarding_days > $trip_total_days) {
						return response()->json(['success' => false, 'errors' => ['Total boarding days should be less than total trip days']]);
					}

					foreach ($request->boardings as $boarding_data) {
						$boarding = Boarding::firstOrNew([
							'id' => $boarding_data['id'],
						]);
						$boarding->fill($boarding_data);
						$boarding->trip_id = $request->trip_id;
						$boarding->from_date = date('Y-m-d', strtotime($boarding_data['from_date']));
						$boarding->to_date = date('Y-m-d', strtotime($boarding_data['to_date']));
						$boarding->created_by = Auth::user()->id;
						$boarding->save();

						//STORE ATTACHMENT
						$item_images = storage_path('app/public/trip/boarding/attachments/');
						Storage::makeDirectory($item_images, 0777);
						if (!empty($boarding_data['attachments'])) {
							foreach ($boarding_data['attachments'] as $key => $attachement) {
								$name = $attachement->getClientOriginalName();
								$attachement->move(storage_path('app/public/trip/boarding/attachments/'), $name);
								$attachement_board = new Attachment;
								$attachement_board->attachment_of_id = 3182;
								$attachement_board->attachment_type_id = 3200;
								$attachement_board->entity_id = $boarding->id;
								$attachement_board->name = $name;
								$attachement_board->save();
							}
						}
					}
				}

				//GET SAVED BOARDINGS
				$saved_boardings = Trip::with([
					'boardings',
					'boardings.city',
					'boardings.attachments',
				])->find($request->trip_id);

				DB::commit();
				return response()->json(['success' => true, 'saved_boardings' => $saved_boardings]);
			}

			//FINAL SAVE LOCAL TRAVELS
			if ($request->is_local_travel) {
				// dd($request->all());
				//GET EMPLOYEE DETAILS
				$employee = Employee::where('id', $request->employee_id)->first();

				//UPDATE TRIP STATUS
				$trip = Trip::find($request->trip_id);

				//CHECK IF EMPLOYEE SELF APPROVE
				if ($employee->self_approve == 1) {
					$trip->status_id = 3025; // Payment Pending
				} else {
					$trip->status_id = 3023; //claimed
				}
				$trip->claim_amount = $request->claim_total_amount; //claimed
				$trip->claimed_date = date('Y-m-d H:i:s');
				$trip->save();

				//SAVE EMPLOYEE CLAIMS
				$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
				$employee_claim->fill($request->all());
				$employee_claim->trip_id = $trip->id;
				$employee_claim->total_amount = $request->claim_total_amount;
				$employee_claim->remarks = $request->remarks;

				//CHECK IS JUSTIFY MY TRIP CHECKBOX CHECKED OR NOT
				if ($request->is_justify_my_trip) {
					$employee_claim->is_justify_my_trip = 1;
				} else {
					$employee_claim->is_justify_my_trip = 0;
				}
				//CHECK IF EMPLOYEE SELF APPROVE
				if ($employee->self_approve == 1) {
					$employee_claim->status_id = 3223; //PAYMENT PENDING
				} else {
					$employee_claim->status_id = 3222; //CLAIM REQUESTED
				}
				//CHECK EMPLOYEE GRADE HAS DEVIATION ELIGIBILITY ==> IF DEVIATION ELIGIBILITY IS 2-NO MEANS THERE IS NO DEVIATION, 1-YES MEANS NEED TO CHECK IN REQUEST
				$grade_advance_eligibility = GradeAdvancedEligiblity::where('grade_id', $request->grade_id)->first();
				if ($grade_advance_eligibility && $grade_advance_eligibility->deviation_eligiblity == 2) {
					$employee_claim->is_deviation = 0; //NO DEVIATION DEFAULT
				} else {
					$employee_claim->is_deviation = $request->is_deviation;
				}
				$employee_claim->created_by = Auth::user()->id;
				$employee_claim->save();

				//STORE GOOGLE ATTACHMENT
				$item_images = storage_path('app/public/trip/ey_employee_claims/google_attachments/');
				Storage::makeDirectory($item_images, 0777);
				if ($request->hasfile('google_attachments')) {
					foreach ($request->file('google_attachments') as $image) {
						$name = $image->getClientOriginalName();
						$image->move(storage_path('app/public/trip/ey_employee_claims/google_attachments/'), $name);
						$attachement = new Attachment;
						$attachement->attachment_of_id = 3185;
						$attachement->attachment_type_id = 3200;
						$attachement->entity_id = $employee_claim->id;
						$attachement->name = $name;
						$attachement->save();
					}

				}

				$activity['entity_id'] = $trip->id;
				$activity['entity_type'] = "Trip";
				$activity['details'] = "Trip is Claimed";
				$activity['activity'] = "claim";
				$activity_log = ActivityLog::saveLog($activity);

				if (!empty($request->local_travels_removal_id)) {
					$local_travels_removal_id = json_decode($request->local_travels_removal_id, true);
					LocalTravel::whereIn('id', $local_travels_removal_id)->delete();
				}
				if ($request->local_travels) {
					foreach ($request->local_travels as $local_travel_data) {
						$local_travel = LocalTravel::firstOrNew([
							'id' => $local_travel_data['id'],
						]);
						$local_travel->fill($local_travel_data);
						$local_travel->trip_id = $request->trip_id;
						$local_travel->date = date('Y-m-d', strtotime($local_travel_data['date']));
						$local_travel->created_by = Auth::user()->id;
						$local_travel->save();

						//STORE ATTACHMENT
						$item_images = storage_path('app/public/trip/local_travels/attachments/');
						Storage::makeDirectory($item_images, 0777);
						if (!empty($local_travel_data['attachments'])) {
							foreach ($local_travel_data['attachments'] as $key => $attachement) {
								$name = $attachement->getClientOriginalName();
								$attachement->move(storage_path('app/public/trip/local_travels/attachments/'), $name);
								$attachement_local_travel = new Attachment;
								$attachement_local_travel->attachment_of_id = 3183;
								$attachement_local_travel->attachment_type_id = 3200;
								$attachement_local_travel->entity_id = $local_travel->id;
								$attachement_local_travel->name = $name;
								$attachement_local_travel->save();
							}
						}
					}
				}

				DB::commit();
				return response()->json(['success' => true]);
			}

			$request->session()->flash('success', 'Trip saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
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
		if (!empty($request->travel_mode_id)) {
			$travel_mode_category_type = DB::table('travel_mode_category_type')->where('travel_mode_id', $request->travel_mode_id)->where('category_id', 3402)->first();
			if ($travel_mode_category_type) {
				$is_no_vehicl_claim = true;
			} else {
				$is_no_vehicl_claim = false;
			}
		} else {
			$is_no_vehicl_claim = false;
		}
		return response()->json(['is_no_vehicl_claim' => $is_no_vehicl_claim]);
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

}
