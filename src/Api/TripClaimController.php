<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\ActivityLog;
use Uitoux\EYatra\Attachment;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\Trip;

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
		return Trip::saveEYatraTripClaim($request);
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

	//GET TRAVEL MODE CATEGORY STATUS TO CHECK IF IT IS NO VEHICLE CLAIM
	public function getVisitTrnasportModeClaimStatus(Request $request) {
		return Trip::getVisitTrnasportModeClaimStatus($request);
	}
	public function getTripClaimAttachments(Request $request) {
		if ($request->trip_id) {
			if ($request->is_lodging == 1) {
				$lodging_images = storage_path('app/public/trip/lodgings/attachments/');
				Storage::makeDirectory($lodging_images, 0777);
				if (!empty($request->attachments)) {
					foreach ($request->attachments as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $request->trip_id . '_lodgings_attachment' . $value . '.' . $extension;
						$attachement->move(storage_path('app/public/trip/lodgings/attachments/'), $name);
						$attachement_lodge = new Attachment;
						$attachement_lodge->attachment_of_id = 3181;
						$attachement_lodge->attachment_type_id = 3200;
						$attachement_lodge->entity_id = $request->trip_id;
						$attachement_lodge->name = $name;
						$attachement_lodge->save();
					}
				}
			}
			if ($request->is_boarding == 1) {

				$boarding_images = storage_path('app/public/trip/boarding/attachments/');
				Storage::makeDirectory($boarding_images, 0777);
				if (!empty($request->attachments)) {
					foreach ($request->attachments as $key => $attachement) {
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
			}

			if ($request->google_attachment == 1) {

				$trip_id = $request->trip_id;

				$employee_claim = EmployeeClaim::where('trip_id', $trip_id)->first();

				$item_images = storage_path('app/public/trip/ey_employee_claims/google_attachments/');
				Storage::makeDirectory($item_images, 0777);
				if ($request->hasfile('google_attachments')) {
					foreach ($request->file('google_attachments') as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $request->trip_id . 'google_attachment' . $value . '.' . $extension;
						$image->move(storage_path('app/public/trip/ey_employee_claims/google_attachments/'), $name);
						$attachement = new Attachment;
						$attachement->attachment_of_id = 3185;
						$attachement->attachment_type_id = 3200;
						$attachement->entity_id = $employee_claim->id;
						$attachement->name = $name;
						$attachement->save();
					}

				}
			}
			return response()->json(['success' => true]);
		}

	}
}
