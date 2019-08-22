<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\Attachment;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Uitoux\EYatra\VisitBooking;
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
				<a href="#!/eyatra/trip/verification/form/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
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
		return response()->json($this->data);
	}

	public function saveTripBookingUpdates(Request $r) {
		dd($r->all());
		DB::beginTransaction();
		try {
			// $validator = Validator::make($r->all(), [
			// 	// 'travel_mode_id' => [
			// 	// 	'required:true',
			// 	// ],
			// 	'reference_number' => [
			// 		'required:true',
			// 	],
			// 	'amount' => [
			// 		'required:true',
			// 	],
			// 	'tax' => [
			// 		'required:true',
			// 	],
			// 	'attachments.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
			// ]);

			// if ($validator->fails()) {
			// 	return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			// }

			//Check Booking Method Agent of Self
			if ($r->booking_method == 'self') {
				//Unique validation
				$travel_mode_id_unique = VisitBooking::where('visit_id', $r->visit_id)
					->where('type_id', $r->type_id)
					->where('travel_mode_id', $r->travel_mode_id)
					->first();
				if ($travel_mode_id_unique) {
					return response()->json(['success' => false, 'errors' => ['Travel mode is already  taken']]);
				}
				$travel_mode_id = $r->travel_mode_id;
				$booking_status_id = 3061; //Visit Status Booked

			} else {
				//check Booking Type Fresh Booking or Cancel Booking
				if ($r->booking_type == 'fresh_booking') {
					$booking_status_id = 3061; //Visit Status Booked
				} else {
					//Find Visit Details
					$visit_status = VisitBooking::where('visit_id', $r->visit_id)
						->where('type_id', 3100)
						->first();
					if (!$visit_status) {
						return response()->json(['success' => false, 'errors' => ['Visit Details not found']]);
					}
					$travel_mode_id = $visit_status->travel_mode_id;
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
				$total_amount = $r->amount + $r->tax + $service_charge;

				$visit_bookings = new VisitBooking;
				$visit_bookings->fill($r->all());
				$visit_bookings->service_charge = $service_charge;
				$visit_bookings->total = $total_amount;
				$visit_bookings->created_by = Auth::user()->id;
				$visit_bookings->save();

				$booking_updates_images = storage_path('app/public/visit/booking-updates/attachments/');
				Storage::makeDirectory($booking_updates_images, 0777);
				if ($r->hasfile('attachments')) {
					foreach ($r->file('attachments') as $image) {
						$name = $image->getClientOriginalName();
						$image->move(storage_path('app/public/visit/booking-updates/attachments/'), $name);
						$attachement = new Attachment;
						$attachement->attachment_of_id = 3180; // Visit Booking Attachment
						$attachement->attachment_type_id = 3200; //Multi Attachment
						$attachement->entity_id = $r->visit_id;
						$attachement->name = $name;
						$attachement->save();
					}
				}
			} else {

				foreach ($r->ticket_booking as $key => $value) {
					$visit = Visit::find($value['visit_id']);
					$visit->booking_status_id = $booking_status_id;
					$visit->save();

					$service_charge = Agent::join('state_agent_travel_mode', 'state_agent_travel_mode.agent_id', 'agents.id')
						->where('state_agent_travel_mode.agent_id', $visit->agent_id)
						->where('state_agent_travel_mode.travel_mode_id', $value['travel_mode_id'])
						->pluck('state_agent_travel_mode.service_charge')->first();

					$service_charge = $service_charge ? $service_charge : 0;
					$amount = $value['ticket_amount'] ? $value['ticket_amount'] : 0;
					$tax = $value['tax'] ? $value['tax'] : 0;

					$total_amount = $amount + $tax + $service_charge;

					$visit_bookings = new VisitBooking;
					$visit_bookings->visit_id = $value['visit_id'];
					$visit_bookings->type_id = $r->type_id;
					$visit_bookings->travel_mode_id = $value['travel_mode_id'];
					$visit_bookings->reference_number = $value['visit_id'];
					$visit_bookings->amount = $amount;
					$visit_bookings->tax = $tax;
					$visit_bookings->service_charge = $service_charge;
					$visit_bookings->total = $total_amount;
					$visit_bookings->status_id = $r->status_id;
					$visit_bookings->created_by = Auth::user()->id;
					$visit_bookings->save();

					$booking_updates_images = storage_path('app/public/visit/booking-updates/attachments/');
					Storage::makeDirectory($booking_updates_images, 0777);
					if ($r->hasfile('attachments')) {
						foreach ($r->file('attachments') as $image) {
							$name = $image->getClientOriginalName();
							$image->move(storage_path('app/public/visit/booking-updates/attachments/'), $name);
							$attachement = new Attachment;
							$attachement->attachment_of_id = 3180; // Visit Booking Attachment
							$attachement->attachment_type_id = 3200; //Multi Attachment
							$attachement->entity_id = $value['visit_id'];
							$attachement->name = $name;
							$attachement->save();
						}
					}
				}
			}
			DB::commit();
		} catch (Exception $e) {
			DB::rollBack();
			// dd($e->getMessage());
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
		return response()->json(['success' => true]);
	}

}
