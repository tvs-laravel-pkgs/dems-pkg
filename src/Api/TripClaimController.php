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

	public function listClaimList(Request $r) {
		$trips = Trip::getEmployeeClaimList($r);
		$trips = $trips
		// ->whereRaw('MAX(v.date) < CURDATE()')
		->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}
	public function getGstin(Request $r){
	   return app('App\Http\Controllers\AngularController')->verifyGSTIN($r->gst_number,"",false);
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
			// if ($trip->advance_received > $employee_claim->claim_total_amount) {
			// 	$trip->status_id = 3031; // Payment Pending for Employee
			// 	$employee_claim->status_id = 3031;
			// } else {
			// 	$trip->status_id = 3025; // Payment Pending for Financier
			// 	$employee_claim->status_id = 3025;
			// }
			$employee_claim->status_id = 3034; //PAYMENT PENDING
			$trip->status_id = 3034; // Payment Pending
		} else {
			$employee_claim->status_id = 3029; //Senior Manager Approval Pending
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
		$employee_claim->status_id = 3024; //Claim Rejected
		$employee_claim->save();

		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip->status_id = 3024; //Claim Rejected
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = "Manager Rejected Employee Claim";
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
			if ($request->is_transport == 1) {
				$lodging_images = storage_path('app/public/trip/transport/attachments/');
				Storage::makeDirectory($lodging_images, 0777);
				if (!empty($request->transport_attachments)) {
					foreach ($request->transport_attachments as $key => $attachement) {
						$value = rand(1, 100);
						$image = $attachement;
						$extension = $image->getClientOriginalExtension();
						$name = $request->trip_id . '_transport_attachment' . $value . '.' . $extension;
						$attachement->move(storage_path('app/public/trip/transport/attachments/'), $name);
						$attachement_lodge = new Attachment;
						$attachement_lodge->attachment_of_id = 3189;
						$attachement_lodge->attachment_type_id = 3200;
						$attachement_lodge->entity_id = $request->trip_id;
						$attachement_lodge->name = $name;
						$attachement_lodge->save();
					}
				}
			}
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
						$attachement->entity_id = $trip_id;
						$attachement->name = $name;
						$attachement->save();
					}

				}
			}
			return response()->json(['success' => true]);
		}

	}

	public function getdigitalsignatureAttachments(Request $request) {

		$lodging_images = storage_path('app/public/petty-cash/signature/');
		Storage::makeDirectory($lodging_images, 0777);
		if (!empty($request->attachments)) {
			foreach ($request->attachments as $key => $attachement) {
				$value = rand(1, 100);
				$image = $attachement;
				$extension = $image->getClientOriginalExtension();
				$name = 'attach_attachment' . $value . '.' . $extension;
				$attachement->move(storage_path('app/public/petty-cash/signature/'), $name);
				// $attachement_lodge = new Attachment;
				// $attachement_lodge->attachment_of_id = 3181;
				// $attachement_lodge->attachment_type_id = 3200;
				// $attachement_lodge->entity_id = $request->trip_id;
				// $attachement_lodge->name = $name;
				// $attachement_lodge->save();
			}
		}

		return response()->json(['success' => true]);

		// if ($request->trip_id) {
		// 	if ($request->is_lodging == 1) {
		// 		$lodging_images = storage_path('app/public/trip/lodgings/attachments/');
		// 		Storage::makeDirectory($lodging_images, 0777);
		// 		if (!empty($request->attachments)) {
		// 			foreach ($request->attachments as $key => $attachement) {
		// 				$value = rand(1, 100);
		// 				$image = $attachement;
		// 				$extension = $image->getClientOriginalExtension();
		// 				$name = $request->trip_id . '_lodgings_attachment' . $value . '.' . $extension;
		// 				$attachement->move(storage_path('app/public/trip/lodgings/attachments/'), $name);
		// 				$attachement_lodge = new Attachment;
		// 				$attachement_lodge->attachment_of_id = 3181;
		// 				$attachement_lodge->attachment_type_id = 3200;
		// 				$attachement_lodge->entity_id = $request->trip_id;
		// 				$attachement_lodge->name = $name;
		// 				$attachement_lodge->save();
		// 			}
		// 		}
		// 	}

		// 	return response()->json(['success' => true]);
		// }

	}
	public function uploadTripDocument(Request $r) {
		// dd($r->all());
		try {
			// validation
			$error_messages = [
                'id.required' => 'Trip request is required',
                'id.integer' => 'Trip request is not correct format',
                'id.exists' => 'Trip request is not found',
                'document_type_id.required' => 'Document type is required',
                'document_type_id.integer' => 'Document type is not correct format',
                'document_type_id.exists' => 'Document type is not found',
                'atttachment.required' => 'Document is required',
            ];
            $validations = [
                'id' => 'required|integer|exists:trips,id',
                'document_type_id' => 'required|integer|exists:configs,id',
                'atttachment' => 'required',
            ];
            $validator = Validator::make($r->all(), $validations , $error_messages);
						
			if ($validator->fails()) {
                return response()->json([
                    'success' => false,
					'message' => 'Validation Errors',
                    'errors' => $validator->errors()->all(),
                ]);
			}

			DB::beginTransaction();
			
			$trip = Trip::find($r->id);
			$item_images = storage_path('app/public/trip/claim/' . $trip->id . '/');
			Storage::makeDirectory($item_images, 0777);
			if (!empty($r->atttachment)) {
				$image = $r->atttachment;
				
				$file_name = $image->getClientOriginalName();
				$attachment_id = Attachment::orderBy('id', 'DESC')->pluck('id')->first() + 1;
                $file_name = 'trip_' . str_replace('.', '_' . $attachment_id . '.', $file_name);
                $file_name = str_replace(' ', '_', $file_name);

				$image->move(storage_path('app/public/trip/claim/' . $trip->id . '/'), $file_name);
				$attachement_transport = Attachment::firstOrNew([
					'attachment_of_id' => $r->document_type_id,
					'attachment_type_id' => 3200,
					'entity_id' => $trip->id,
				]);
				$attachement_transport->attachment_of_id = $r->document_type_id;
				$attachement_transport->attachment_type_id = 3200;
				$attachement_transport->entity_id = $trip->id;
				$attachement_transport->name = $file_name;
				$attachement_transport->save();
			}

			DB::commit();
			$attachment_type_lists = Trip::getAttachmentList($trip->id);
			$trip = Trip::with([
				'tripAttachments',
				'tripAttachments.attachmentName',
			])->find($trip->id);
			$trip_attachments = isset($trip->tripAttachments) ? $trip->tripAttachments : [];

			return response()->json([
				'success' => true,
				'message' => 'Document uploaded successfully!',
				'attachment_type_lists' => $attachment_type_lists,
				'trip_attachments' => $trip_attachments
			]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteTripDocument(Request $r) {
		// dd($r->all());
		try {
			// validation
			$error_messages = [
                'attachment_id.required' => 'Attachment is required',
                'attachment_id.integer' => 'Attachment is not correct format',
                'attachment_id.exists' => 'Attachment is not found',
            ];
            $validations = [
                'attachment_id' => 'required|integer|exists:attachments,id',
            ];
            $validator = Validator::make($r->all(), $validations , $error_messages);
						
			if ($validator->fails()) {
                return response()->json([
                    'success' => false,
					'message' => 'Validation Errors',
                    'errors' => $validator->errors()->all(),
                ]);
			}

			DB::beginTransaction();
			
			$attachment = Attachment::where('id', $r->attachment_id)->first();
			$trip_id = null;
			if ($attachment) {
				$trip_id = $attachment->entity_id;
				$destination = 'public/trip/claim/' . $trip_id . '/' . $attachment->name;
				Storage::makeDirectory($destination, 0777);
				Storage::disk('local')->delete($destination . '/' . $attachment->name);
				$attachment->forceDelete();
			}


			DB::commit();
			$attachment_type_lists = Trip::getAttachmentList($trip_id);
			$trip = Trip::with([
				'tripAttachments',
				'tripAttachments.attachmentName',
			])->find($trip_id);
			$trip_attachments = isset($trip->tripAttachments) ? $trip->tripAttachments : [];

			return response()->json([
				'success' => true,
				'message' => 'Document deleted successfully!',
				'attachment_type_lists' => $attachment_type_lists,
				'trip_attachments' => $trip_attachments
			]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
