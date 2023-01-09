<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mail;
use Uitoux\EYatra\Attachment;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\Mail\TicketNotificationMail;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Uitoux\EYatra\VisitBooking;
use Validator;
use Yajra\Datatables\Datatables;

class TripBookingUpdateController extends Controller {
	public function listTripBookingUpdates(Request $r) {
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
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc')
			->orderBy('trips.status_id', 'desc')
		;

		if (!Entrust::can('trip-verification-all')) {
			$trips->where('trips.manager_id', Auth::user()->entity_id);
		}
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/trip/verification/form/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
	}

	public function changeBookingTatkal($visit_id) {
		$visit = Visit::find($visit_id)->update(['booking_status_id' => 3063]);
		if ($visit) {
			return response()->json(['success' => true]);
		} else {
			return response()->json(['success' => false, 'errors' => ['Visit not found']]);
		}
	}
	public function tripBookingUpdatesFormData($visit_id) {

		$visit = Visit::with([
			'fromCity',
			'toCity',
			'trip',
		])
			->find($visit_id);

		if (!$visit) {
			return response()->json(['success' => false, 'errors' => ['Visit not found']]);
		}

		// if (!Entrust::can('trip-verification-all') && $trip->manager_id != Auth::user()->entity_id) {
		// 	return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
		// }
		$this->data['visit'] = $visit;
		// $this->data['travel_mode_list'] = Entity::uiTravelModeList();
		$grade = Auth::user()->entity;
		$this->data['travel_mode_list'] = DB::table('grade_travel_mode')->select('travel_mode_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_travel_mode.travel_mode_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get();
		$this->data['success'] = true;
		$visit->employee_gst_code = NState::leftJoin('ncities', 'ncities.state_id', 'nstates.id')
			->leftJoin('ey_addresses', 'ey_addresses.city_id', 'ncities.id')
			->where('ey_addresses.address_of_id', 3160)
			->where('ey_addresses.entity_id', $visit->trip->outlet_id)
			->pluck('nstates.gstin_state_code')->first();
		return response()->json($this->data);
	}

	public function saveTripBookingUpdates(Request $r) {
		//dd($r->all());
		DB::beginTransaction();
		try {
			$error_messages = [
				'ticket_booking.*.travel_mode_id.required' => 'Ticket booking mode is required',
				'ticket_booking.*.ticket_amount.required' => 'Ticket amount is required',
				'ticket_booking.*.reference_number.required' => 'Ticket reference_number is required',
				 //'ticket_booking.*.attachments.required' => 'Ticket attachments is required',
			];

			$validator = Validator::make($r->all(), [
				'ticket_booking.*.travel_mode_id' => [
					'required:true',
				],
				'ticket_booking.*.ticket_amount' => [
					'required:true',
				],
				'ticket_booking.*.reference_number' => [
					'required:true',
				],
			 //'ticket_booking.*.attachments' => 'required|mimes:jpeg,jpg,txt,pdf',
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			//Check Booking Method Agent of Self
			if ($r->booking_method == 'self') {
				//Unique validation
				$travel_mode_id_unique = VisitBooking::where('visit_id', $r->visit_id)
					->where('type_id', $r->type_id)
					->where('travel_mode_id', $r->travel_mode_id)
					->first();
				if ($travel_mode_id_unique) {
					// return response()->json(['success' => false, 'errors' => ['Travel mode is already  taken']]);
				}
				$travel_mode_id = $r->travel_mode_id;
				$booking_status_id = 3061; //Visit Status Booked

			} else {
				//check Booking Type Fresh Booking or Cancel Booking
				if ($r->booking_type == 'fresh_booking') {
					$booking_status_id = 3061; //Visit Status Booked
				} else {
					//Find Visit Details
					// $visit_status = VisitBooking::where('visit_id', $r->visit_id)
					// 	->where('type_id', 3100)
					// 	->first();
					// if (!$visit_status) {
					// 	return response()->json(['success' => false, 'errors' => ['Visit Details not found']]);
					// }
					// $travel_mode_id = $visit_status->travel_mode_id;
					$booking_status_id = 3062; //Visit Status Cancelled
				}

				// dd($booking_status_id);
			}

			if ($r->booking_method == 'self') {
				//Visit status update
				$visit = Visit::find($r->visit_id);
				$visit->booking_status_id = $booking_status_id;
				$visit->save();

				$service_charge = 0;
				//Total Amount of Booking Deails (include tax)
				$tax = $r->cgst + $r->sgst + $r->igst;
				$tax_total = $tax ? $tax : 0;
				if(!empty($r->round_off) && ($r->round_off > 1 || $r->round_off < -1)){
						return response()->json([
											'success' => false,
											'errors' => ['Round off amount limit is +1 Or -1'],
										]);
					}
				$total_amount = $r->amount + $tax_total + $service_charge+$r->round_off;
                
				$visit_bookings = new VisitBooking;
				$visit_bookings->fill($r->all());
				$visit_bookings->invoice_date = date('Y-m-d', strtotime($r->invoice_date));
				$visit_bookings->service_charge = $service_charge;
				$visit_bookings->total = $total_amount;
				$visit_bookings->created_by = Auth::user()->id;
				$visit_bookings->tax = $tax_total;
				$visit_bookings->cgst = $r->cgst;
				$visit_bookings->sgst = $r->sgst;
				$visit_bookings->igst = $r->igst;

				$visit_bookings->save();

				$booking_updates_images = storage_path('app/public/visit/booking-updates/attachments/');
				Storage::makeDirectory($booking_updates_images, 0777);

				// if (!empty($r->attachments)) {
				// 	// dd($r->attachments);
				// 	foreach ($r->attachments as $key => $attachement) {
				// 		// dump($attachement);

				// 		$value = rand(1, 100);
				// 		$image = $attachement;
				// 		$extension = $image->getClientOriginalExtension();
				// 		$name = '1111_lodgings_attachment' . $value . '.' . $extension;
				// 		$attachement->move(storage_path('app/public/visit/booking-updates/attachments/'), $name);
				// 		$attachement_lodge = new Attachment;
				// 		$attachement_lodge->attachment_of_id = 3180;
				// 		$attachement_lodge->attachment_type_id = 3200;
				// 		$attachement_lodge->entity_id = $value;
				// 		$attachement_lodge->name = $name;
				// 		$attachement_lodge->save();
				// 	}
				// }

				if (!empty($r->attachment)) {
					$value = rand(1, 100);
					$image = $r->attachment;
					$extension = $image->getClientOriginalExtension();
					if($extension != 'jpeg' && $extension != 'jpg' && $extension != 'txt' && $extension != 'pdf'){
							return response()->json(['success' => false,'errors' => ['Valid File format are jpeg , jpg,txt,pdf.'],
										]);
						}
					$name = $visit_bookings->id . '_ticket_booking_attachment' . $value . '.' . $extension;
					$des_path = storage_path('app/public/visit/booking-updates/attachments/');
					$image->move($des_path, $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 3180; // Visit Booking Attachment
					$attachement->attachment_type_id = 3200; //Multi Attachment
					$attachement->entity_id = $visit_bookings->id;
					$attachement->name = $name;
					$attachement->save();
				}
			} else {
				//AGENT BOOKING TRIP
				foreach ($r->ticket_booking as $key => $value) {
					$visit = Visit::find($value['visit_id']);
					$visit->booking_status_id = $booking_status_id;
					$visit->save();
					$trip = Trip::find($visit->trip_id);
					// dump($trip);
					$state = $trip->employee->outlet->address->city->state;
					$service_charge = Agent::join('state_agent_travel_mode', 'state_agent_travel_mode.agent_id', 'agents.id')
						->where('state_agent_travel_mode.agent_id', $visit->agent_id)
						->where('state_agent_travel_mode.state_id', $state->id)
						->where('state_agent_travel_mode.travel_mode_id', $value['travel_mode_id'])
						->pluck('state_agent_travel_mode.service_charge')->first();
					$service_charge = $service_charge ? $service_charge : 0;
					$amount = $value['ticket_amount'] ? $value['ticket_amount'] : 0;
					$tax_total = $value['cgst'] + $value['sgst'] + $value['igst'];
					$tax = $tax_total ? $tax_total : 0;
                    $round_off=$value['round_off'] ? $value['round_off']:0;
					$total_amount = $amount + $tax + $service_charge+$round_off;
					//Agent Claimed Amount
					if ($r->booking_type == 'fresh_booking') {
						$claim_amount = $total_amount;
					} else {
						//Get Same Visit Booking Details
						$previous_visit_booking = VisitBooking::where('visit_id', $value['visit_id'])
							->where('type_id', 3100)
							->select('amount', 'tax', 'service_charge')
							->first();

						//Update Same Visit Booking details paid/claim amount
						$visit_booking_update = VisitBooking::where('visit_id', $value['visit_id'])
							->where('type_id', 3100)
							->update(['paid_amount' => $service_charge]);

						$previous_booking_amount = $previous_visit_booking->amount + $previous_visit_booking->tax;
						$cancel_booking_amount = $amount + $tax;
						$claim_amount = ($previous_booking_amount - $cancel_booking_amount) + $service_charge;

					}

					if (!empty($value['visit_booking_id'])) {
						$visit_bookings = VisitBooking::find($value['visit_booking_id']);
					} else {
						$visit_bookings = new VisitBooking;
					}
					$visit_bookings->visit_id = $value['visit_id'];
					$visit_bookings->type_id = $r->type_id;
					$visit_bookings->travel_mode_id = $value['travel_mode_id'];
					$visit_bookings->reference_number = $value['reference_number'];
					if(!empty($value['round_off']) && ($value['round_off'] > 1 || $value['round_off'] < -1)){
						return response()->json([
											'success' => false,
											'errors' => ['Round off amount limit is +1 Or -1'],
										]);
					}
					if (!empty($value['gstin'])) {
							$response = app('App\Http\Controllers\AngularController')->verifyGSTIN($value['gstin'], "", false);
					    if (!$response['success']) {
							return response()->json(['success' => false,'errors' => [$response['error'],],]);
						}
						$visit_bookings->gstin = $response['gstin'];
					} else {
						$visit_bookings->gstin = NULL;
					}
					$visit_bookings->fill($value);
					$visit_bookings->invoice_date = date('Y-m-d', strtotime($value['invoice_date']));
					//$visit_bookings->invoice_number = $value['invoice_number'];
					// $visit_bookings->booking_type_id = $value['booking_mode_id'];
					//$visit_bookings->booking_category_id = $value['booking_category_id'];
					//$visit_bookings->booking_method_id = $value['booking_method_id'];
					//$visit_bookings->agent_service_charges = $value['agent_service_charges'];
					$visit_bookings->amount = $amount;
					$visit_bookings->tax = $tax;
					//$visit_bookings->tax_percentage = $value['tax_percentage'];
					//$visit_bookings->gstin = $value['gstin'];
					$visit_bookings->cgst = $value['cgst'];
					$visit_bookings->sgst = $value['sgst'];
					$visit_bookings->igst = $value['igst'];
					//$visit_bookings->other_charges = $value['other_charges'];
					$visit_bookings->service_charge = $service_charge;
					// $visit_bookings->total = $total_amount;
					$visit_bookings->total = round($value['total']);
					//dd($visit_bookings->total,$value['total']);
					$visit_bookings->paid_amount = $claim_amount;
					$visit_bookings->status_id = $r->status_id;
					$visit_bookings->created_by = Auth::user()->id;
					$visit_bookings->save();

					$booking_updates_images = storage_path('app/public/visit/booking-updates/attachments/');
					Storage::makeDirectory($booking_updates_images, 0777);
					if (isset($value['attachments'])) {
						$image = $value['attachments'];
						// foreach ($r->file('attachments') as $image) {
						$extension = $image->getClientOriginalExtension();
						if($extension != 'jpeg' && $extension != 'jpg' && $extension != 'txt' && $extension != 'pdf'){
							return response()->json(['success' => false,'errors' => ['Valid File format are jpeg , jpg,txt,pdf.'],
										]);
						}
						$value = rand(1, 100);
						$name = $visit_bookings->id . '_ticket_booking_attachment' . $value . '.' . $extension;
						$des_path = storage_path('app/public/visit/booking-updates/attachments/');
						$image->move($des_path, $name);
						$attachement = new Attachment;
						$attachement->attachment_of_id = 3180; // Visit Booking Attachment
						$attachement->attachment_type_id = 3200; //Multi Attachment
						$attachement->entity_id = $visit_bookings->id;
						$attachement->name = $name;
						$attachement->save();

						// PROOF ATTACHED
						$visit_bookings->is_proof_attached = 1;
						$visit_bookings->save();
						// }
					}

					//Ticket Employee Mail Trigger
					if ($visit) {
						if ($visit->booking_status_id == 3061) {
							$this->sendTicketNotificationMail($type = 16, $visit);
							$employee = Employee::where('id', $trip->employee_id)->first();
							$user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();
							$notification = sendnotification($type = 16, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Ticket Booking Mail');
						}
						if ($visit->booking_status_id == 3062) {
							$employee = Employee::where('id', $trip->employee_id)->first();
							$user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();
							$notification = sendnotification($type = 18, $trip, $user, $trip_type = "Outstation Trip", $notification_type = 'Ticket Cancelled');
						}
					}
				}
			}
			DB::commit();
			return response()->json([
				'success' => true,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => [
					'Exception Error' => $e->getMessage(),
				],
			]);
		}
	}

	public function saveTripBookingProofUpload(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'visit_id' => [
					'required:true',
					'integer',
					'exists:visits,id',
				],
				'proof_attachment' => 'required|mimes:jpeg,jpg,txt,pdf',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$visit = Visit::find($request->visit_id);
			$trip = Trip::find($visit->trip_id);

			//SAVE VISIT BOOKING
			$visitBooking = VisitBooking::firstOrNew([
				'visit_id' => $request->visit_id,
			]);
			$visitBooking->visit_id = $request->visit_id;
			$visitBooking->is_proof_attached = 1;
			$visitBooking->type_id = 3100; //Booking
			$visitBooking->status_id = 3240; //Claim Pending
			$visitBooking->created_by = Auth::user()->id;
			$visitBooking->save();

			$removeAttachments = Attachment::where('attachment_of_id', 3180) // Visit Booking Attachment
				->where('attachment_type_id', 3200) //Multi Attachment
				->where('entity_id', $visitBooking->id)
				->get();
			if ($removeAttachments) {
				if ($removeAttachments->isNotEmpty()) {
					foreach ($removeAttachments as $ket => $removeAttachment) {
						if (Storage::disk('local')->exists('/public/visit/booking-updates/attachments/' . $removeAttachment->name)) {
							unlink(storage_path('app/public/visit/booking-updates/attachments/' . $removeAttachment->name));
						}
						$removeAttachment->delete();
					}
				}
			}

			if ($request->hasFile('proof_attachment')) {
				$image = $request->file('proof_attachment');
				$extension = $image->getClientOriginalExtension();
				$value = rand(1, 100);
				$name = $visitBooking->id . '_ticket_booking_attachment' . $value . '.' . $extension;
				$des_path = storage_path('app/public/visit/booking-updates/attachments/');
				$image->move($des_path, $name);

				//SAVE ATTACHMENT
				$attachement = new Attachment;
				$attachement->attachment_of_id = 3180; // Visit Booking Attachment
				$attachement->attachment_type_id = 3200; //Multi Attachment
				$attachement->entity_id = $visitBooking->id;
				$attachement->name = $name;
				$attachement->save();
			}

			if ($visit && $trip) {
				$this->sendTicketNotificationMail(16, $visit);
				$employee = Employee::where('id', $trip->employee_id)->first();
				if ($employee) {
					$user = User::where('entity_id', $employee->reporting_to_id)
						->where('user_type_id', 3121)
						->first();
					sendnotification(16, $trip, $user, "Outstation Trip", 'Ticket Booking Mail');
				}
			}

			DB::commit();
			return response()->json([
				'success' => true,
			]);

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

	public function sendTicketNotificationMail($type, $visit) {
		$from_mail = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
		$from_name = env('MAIL_USERNAME', 'DEMS-Admin');
		$to_user = $visit->trip->employee->user;
		$employee_details = $visit->trip->employee;
		$visit_details = Visit::select(
			'visits.id',
			'trips.id as trip_id',
			'users.name as employee_name',
			DB::raw('DATE_FORMAT(visits.departure_date,"%d/%m/%Y") as visit_date'),
			DB::raw('TIME_FORMAT(visits.prefered_departure_time,"h:i A") as prefered_departure_time'),
			'fromcity.name as fromcity_name',
			'tocity.name as tocity_name',
			'travel_modes.name as travel_mode_name',
			'booking_modes.name as booking_method_name',
			'booking_status.name as booking_status'
		)
			->join('trips', 'trips.id', 'visits.trip_id')
			->leftjoin('users', 'trips.employee_id', 'users.id')
			->join('ncities as fromcity', 'fromcity.id', 'visits.from_city_id')
			->join('ncities as tocity', 'tocity.id', 'visits.to_city_id')
			->join('entities as travel_modes', 'travel_modes.id', 'visits.travel_mode_id')
			->join('configs as booking_modes', 'booking_modes.id', 'visits.booking_method_id')
			->join('configs as booking_status', 'booking_status.id', 'visits.booking_status_id')
			->where('booking_method_id', 3042)
			->where('booking_status_id', 3061)
			->where('visits.id', $visit->id)
			->first();
		//dd($visit->bookings);
		$arr['visits_attachments'] = "";
		foreach ($visit->bookings as $key => $booking) {
			//dump($booking->attachments);
			if (count($booking->attachments) > 0) {
				$arr['visits_attachments'] = $booking->attachments;
			}
		}
		//dd($arr['visits_attachments']);
		if ($to_user->email) {
			$arr['from_mail'] = $from_mail;
			$arr['from_name'] = $from_name;
			$arr['to_email'] = $to_user->email;
			$arr['to_name'] = $to_user->name;
			//$arr['to_email'] = $employee->email;
			//$arr['to_name'] = 'parthiban';
			$arr['subject'] = 'Ticket Booking Mail';
			$arr['body'] = 'Employee ticket booking notification';
			$arr['employee_details'] = $employee_details;
			$arr['visits'] = $visit_details;
			$arr['type'] = 16;
			if ($arr['visits_attachments']) {
				$arr['attachment'] = url('storage/app/public/visit/booking-updates/attachments/' . $arr['visits_attachments'][0]['name']);
			} else {
				$arr['attachment'] = "";
			}
//dd($arr['attachment']);
			//dump($visit->bookings);
			//$arr['visits_attachments'] = Attachment::where('entity_id',$visit->bookings)->where('attachment_of_id', 3180)->where('attachment_type_id', 3200)->get();
			//dd($arr['visits_attachments']);
			//$arr['trip'] = $trip;
			//$arr['type'] = 2;
			$MailInstance = new TicketNotificationMail($arr);
			$Mail = Mail::send($MailInstance);
		}
		unset($arr['visits_attachments']);

	}

}
