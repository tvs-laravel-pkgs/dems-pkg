<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Trip;
use Yajra\Datatables\Datatables;

class TripVerificationController extends Controller {
	public function listTripVerification(Request $r) {
		$trips = Trip::getVerficationPendingList();
		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/trip/verification/form/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				';

			})
			->make(true);
	}

	public function tripVerificationFormData($trip_id) {

		$trip = Trip::with([
			'visits',
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'purpose',
			'status',
		])
			->find($trip_id);

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		if (!Entrust::can('trip-verification-all') && $trip->manager_id != Auth::user()->entity_id) {
			return response()->json(['success' => false, 'errors' => ['You are nor authorized to view this trip']]);
		}

		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $start_date->end_date;
		$this->data['trip'] = $trip;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveTripVerification(Request $r) {
		return Trip::saveTripVerification($r)
	}

	public function approveTripVerification($trip_id) {
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->status_id = 3028;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3081]);
		return response()->json(['success' => true]);
	}

	public function rejectTripVerification($trip_id) {
		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->status_id = 3022;
		$trip->save();

		$trip->visits()->update(['manager_verification_status_id' => 3082]);
		return response()->json(['success' => true]);
	}

}
