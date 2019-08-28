<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\Boarding;
use Uitoux\EYatra\EmployeeClaim;
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
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.date),"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)
			->where('trips.status_id', 3023)
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
				return '
				<a href="#!/eyatra/trip/claim/edit/' . $trip->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/trip/claim/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function eyatraTripClaimFormData($trip_id) {
		return Trip::getClaimFormData($trip_id);
	}

	public function saveEYatraTripClaim(Request $request) {
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
			//UPDATE TRIP STATUS
			$trip = Trip::find($request->trip_id);
			$trip->status_id = 3023; //claimed
			$trip->claim_amount = $request->claim_total_amount; //claimed
			$trip->save();

			//SAVE EMPLOYEE CLAIMS
			$employee_claim = EmployeeClaim::firstOrNew(['trip_id' => $trip->id]);
			$employee_claim->fill($request->all());
			$employee_claim->trip_id = $trip->id;
			$employee_claim->total_amount = $request->claim_total_amount;
			$employee_claim->status_id = 3222;
			$employee_claim->created_by = Auth::user()->id;
			$employee_claim->save();
			//SAVING VISITS
			if ($request->visits) {
				foreach ($request->visits as $visit_data) {
					if (!empty($visit_data['id'])) {
						$visit = Visit::find($visit_data['id']);
						$visit->departure_date = date('Y-m-d H:i:s', strtotime($visit_data['departure_date']));
						$visit->arrival_date = date('Y-m-d H:i:s', strtotime($visit_data['arrival_date']));
						$visit->save();
						// dd($visit_data['id']);
						//UPDATE VISIT BOOKING STATUS
						$visit_booking = VisitBooking::firstOrNew(['visit_id' => $visit_data['id']]);
						$visit_booking->visit_id = $visit_data['id'];
						$visit_booking->type_id = 3100;
						$visit_booking->travel_mode_id = $visit_data['travel_mode_id'];
						$visit_booking->reference_number = $visit_data['remarks'];
						$visit_booking->amount = $visit_data['amount'];
						$visit_booking->tax = $visit_data['tax'];
						$visit_booking->service_charge = '0.00';
						$visit_booking->total = $visit_data['total'];
						$visit_booking->paid_amount = $visit_data['total'];
						$visit_booking->created_by = Auth::user()->entity_id;
						$visit_booking->status_id = 3241; //Claimed
						$visit_booking->save();
					}
				}
			}

			//SAVING LODGINGS
			if ($request->lodgings) {
				if (!empty($request->lodgings_removal_id)) {
					$lodgings_removal_id = json_decode($request->lodgings_removal_id, true);
					Lodging::whereIn('id', $lodgings_removal_id)->delete();
				}
				foreach ($request->lodgings as $lodging_data) {
					$lodging = Lodging::firstOrNew([
						'id' => $lodging_data['id'],
					]);
					$lodging->fill($lodging_data);
					$lodging->trip_id = $request->trip_id;
					$lodging->check_in_date = date('Y-m-d H:i:s'); //$lodging_data['check_in_date'];
					$lodging->checkout_date = date('Y-m-d H:i:s'); //$lodging_data['checkout_date'];
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
			//SAVING BOARDINGS
			if ($request->boardings) {
				if (!empty($request->boardings_removal_id)) {
					$boardings_removal_id = json_decode($request->boardings_removal_id, true);
					Boarding::whereIn('id', $boardings_removal_id)->delete();
				}
				foreach ($request->boardings as $boarding_data) {
					$boarding = Boarding::firstOrNew([
						'id' => $boarding_data['id'],
					]);
					$boarding->fill($boarding_data);
					$boarding->trip_id = $request->trip_id;
					$boarding->date = date('Y-m-d', strtotime($boarding_data['date']));
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

			//SAVING LOCAL TRAVELS
			if ($request->local_travels) {
				if (!empty($request->local_travels_removal_id)) {
					$local_travels_removal_id = json_decode($request->local_travels_removal_id, true);
					LocalTravel::whereIn('id', $local_travels_removal_id)->delete();
				}
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
			$city_category_id = NCity::where('id', $request->city_id)->first();
			$grade_expense_type = DB::table('grade_expense_type')->where('grade_id', $request->grade_id)->where('expense_type_id', $request->expense_type_id)->where('city_category_id', $city_category_id->category_id)->first();
		} else {
			$grade_expense_type = [];
		}
		return response()->json(['grade_expense_type' => $grade_expense_type]);
	}
}
