<?php

namespace Uitoux\EYatra\Api;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\Attachment;
use Uitoux\EYatra\LocalTrip;
use Uitoux\EYatra\Trip;

class LocalTripController extends Controller {
	public $successStatus = 200;

	public function listLocalTrip(Request $request) {
		$trips = LocalTrip::getLocalTripList($request);
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);

	}

	public function getTripFormData(Request $request) {
		return LocalTrip::getLocalTripFormData($request->trip_id);
	}

	public function saveLocalTrip(Request $request) {
		// dd($request->all());
		if ($request->id) {
			$trip_start_date_data = LocalTrip::where('employee_id', Auth::user()->entity_id)
				->where('id', '!=', $request->id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
			$trip = LocalTrip::find($request->id);
			if ($trip->status_id >= 3542) {
				if ($request->trip_detail == '') {
					return response()->json(['success' => false, 'errors' => "Please enter atleast one local trip expense to further proceed"]);
				}
			}
		} else {
			$trip_start_date_data = LocalTrip::where('employee_id', Auth::user()->entity_id)
				->whereBetween('start_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->whereBetween('end_date', [date("Y-m-d", strtotime($request->start_date)), date("Y-m-d", strtotime($request->end_date))])
				->first();
		}

		if ($trip_start_date_data) {
			return response()->json(['success' => false, 'errors' => "You have another local trip on this trip period"]);
		}

		if ($request->trip_detail) {
			$size = sizeof($request->trip_detail);
			for ($i = 0; $i < $size; $i++) {
				if (!(($request->trip_detail[$i]['travel_date'] >= $request->start_date) && ($request->trip_detail[$i]['travel_date'] <= $request->end_date))) {
					return response()->json(['success' => false, 'errors' => "Visit date should be within Trip Period"]);

				}

			}
		}
		return LocalTrip::saveTrip($request);
	}

	public function saveAttachments(Request $request) {
		if ($request->google_attachment == 1) {

			$trip_id = $request->trip_id;

			//STORE GOOGLE ATTACHMENT
			$item_images = storage_path('app/public/trip/local-trip/google_attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (!empty($request->google_attachments)) {
				foreach ($request->google_attachments as $key => $attachement) {
					$image = $attachement;
					$extension = $image->getClientOriginalExtension();
					$name = $image->getClientOriginalName();
					$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
					$value = rand(1, 100);
					$extension = $image->getClientOriginalExtension();
					$name = $value . '-' . $file_name;
					$image->move(storage_path('app/public/trip/local-trip/google_attachments/'), $name);
					$attachement_file = new Attachment;
					$attachement_file->attachment_of_id = 3187;
					$attachement_file->attachment_type_id = 3200;
					$attachement_file->entity_id = $trip_id;
					$attachement_file->name = $name;
					$attachement_file->save();
				}

			}
		}
		if ($request->expense_attachment == 1) {

			$trip_id = $request->trip_id;

			//SAVE EXPENSE ATTACHMENT
			$item_images = storage_path('app/public/trip/local-trip/attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (!empty($request->expense_attachments)) {
				foreach ($request->expense_attachments as $key => $attachement) {
					$image = $attachement;
					$extension = $image->getClientOriginalExtension();
					$name = $image->getClientOriginalName();
					$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
					$value = rand(1, 100);
					$extension = $image->getClientOriginalExtension();
					$name = $trip_id . $value . '-Travel-expense-' . $file_name;
					$image->move(storage_path('app/public/trip/local-trip/attachments/'), $name);
					$attachement_file = new Attachment;
					$attachement_file->attachment_of_id = 3186;
					$attachement_file->attachment_type_id = 3200;
					$attachement_file->entity_id = $trip_id;
					$attachement_file->name = $name;
					$attachement_file->save();
				}
			}
		}

		if ($request->other_expense_attachment == 1) {

			$trip_id = $request->trip_id;

			//SAVE OTHER EXPENSE ATTACHMENT
			$item_images = storage_path('app/public/trip/local-trip/attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (!empty($request->other_expense_attachments)) {
				foreach ($request->other_expense_attachments as $key => $attachement) {
					$image = $attachement;
					$extension = $image->getClientOriginalExtension();
					$name = $image->getClientOriginalName();
					$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
					$value = rand(1, 100);
					$extension = $image->getClientOriginalExtension();
					$name = $trip_id . $value . '-Other-expense-' . $file_name;
					$image->move(storage_path('app/public/trip/local-trip/attachments/'), $name);
					$attachement_file = new Attachment;
					$attachement_file->attachment_of_id = 3188;
					$attachement_file->attachment_type_id = 3200;
					$attachement_file->entity_id = $trip->id;
					$attachement_file->name = $name;
					$attachement_file->save();
				}
			}
		}
		return response()->json(['success' => true]);

	}
	public function viewTrip($trip_id, Request $request) {
		return LocalTrip::getViewData($trip_id);
	}

	public function getDashboard() {
		return Trip::getDashboardData();

	}

	public function deleteTrip($trip_id) {
		return LocalTrip::deleteTrip($trip_id);
	}

	public function cancelTrip($trip_id) {
		return LocalTrip::cancelTrip($trip_id);
	}

	public function listTripVerification(Request $r) {
		$trips = LocalTrip::getVerficationPendingList($r);
		$trips = $trips->get()
		;
		return response()->json(['success' => true, 'trips' => $trips]);
	}

	public function approveTrip($trip_id) {
		return LocalTrip::approveTrip($trip_id);
	}

	public function rejectTrip(Request $request) {
		return LocalTrip::rejectTrip($request);
	}
}
