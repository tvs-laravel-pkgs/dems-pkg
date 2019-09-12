<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\ActivityLog;
use Uitoux\EYatra\Boarding;
use Uitoux\EYatra\GradeAdvancedEligiblity;
use Uitoux\EYatra\LocalTravel;
use Uitoux\EYatra\Lodging;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Uitoux\EYatra\VisitBooking;

class TripClaimController extends Controller {
	public $successStatus = 200;

	public function listCompletedTrips(Request $r) {
		$trips = Trip::getEmployeeList($r);
		$trips = $trips
		// ->whereRaw('MAX(v.date) < CURDATE()')
		->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function getClaimFormData($trip_id) {
		return Trip::getClaimFormData($trip_id);
	}

	public function getClaimViewData($trip_id) {
		return Trip::getClaimViewData($trip_id);
	}

	public function saveClaim(Request $request) {
		// dd($r->all());
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

	public function approveTripClaimVerificationOne($trip_id) {

		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim = EmployeeClaim::where('trip_id', $trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		if ($employee_claim->is_deviation == 0) {
			$employee_claim->status_id = 3223; //Payment Pending
			$trip->status_id = 3025; //Payment Pending
		} else {
			$employee_claim->status_id = 3224; //Senior Manager Approval Pending
			$trip->status_id = 3029; //Senior Manager Approval Pending
		}
		$employee_claim->save();
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Employee Claims V1 Approved";
		$activity['activity'] = "approve";
		$activity_log = ActivityLog::saveLog($activity);
		return response()->json(['success' => true]);
	}

	public function rejectTripClaimVerificationOne(Request $r) {

		$trip = Trip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$employee_claim = EmployeeClaim::where('trip_id', $r->trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim->status_id = 3226; //Claim Rejected
		$employee_claim->save();

		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3024; //Claim Rejected
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Employee Claims V1 Rejected";
		$activity['activity'] = "reject";
		$activity_log = ActivityLog::saveLog($activity);
		return response()->json(['success' => true]);
	}
}
