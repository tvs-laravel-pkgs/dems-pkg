<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Boarding;
use Uitoux\EYatra\EmployeeClaim;
use Uitoux\EYatra\LocalTravel;
use Uitoux\EYatra\Lodging;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Yajra\Datatables\Datatables;

class TripClaimVerificationTwoController extends Controller {
	public function listEYatraTripClaimVerificationTwoList(Request $r) {
		$trips = EmployeeClaim::join('trips', 'trips.id', 'ey_employee_claims.trip_id')
			->join('visits as v', 'v.trip_id', 'trips.id')
			->join('ncities as c', 'c.id', 'v.from_city_id')
			->join('employees as e', 'e.id', 'trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'trips.purpose_id')
			->join('configs as status', 'status.id', 'trips.status_id')
			->leftJoin('users', 'users.entity_id', 'trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'trips.id',
				'trips.number',
				'e.code as ecode',
				'users.name as ename',
				DB::raw('GROUP_CONCAT(DISTINCT(c.name)) as cities'),
				DB::raw('DATE_FORMAT(MIN(v.departure_date),"%d/%m/%Y") as start_date'),
				DB::raw('DATE_FORMAT(MAX(v.departure_date),"%d/%m/%Y") as end_date'),
				'purpose.name as purpose',
				'trips.advance_received',
				'status.name as status'
			)
			->where('e.company_id', Auth::user()->company_id)
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
			->where('ey_employee_claims.status_id', 3224) //SENIOR MANAGER APPROVAL PENDING
			->where('e.reporting_to_id', Auth::user()->entity_id) //MANAGER
			->groupBy('trips.id')
			->orderBy('trips.created_at', 'desc');

		return Datatables::of($trips)
			->addColumn('action', function ($trip) {

				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');

				return '
				<a href="#!/eyatra/trip/claim/verification2/view/' . $trip->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function viewEYatraTripClaimVerificationTwo($trip_id) {

		if (!$trip_id) {
			$this->data['success'] = false;
			$this->data['message'] = 'Trip not found';
		} else {
			$trip = Trip::with([
				'advanceRequestStatus',
				'employee',
				'employee.user',
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
				'lodgings.city',
				'lodgings.stateType',
				'lodgings.attachments',
				'boardings',
				'boardings.city',
				'boardings.attachments',
				'localTravels',
				'localTravels.fromCity',
				'localTravels.toCity',
				'localTravels.travelMode',
				'localTravels.attachments',
				'selfVisits.fromCity',
				'selfVisits.toCity',
				'selfVisits.travelMode',
				'selfVisits.bookingMethod',
				'selfVisits.selfBooking',
				'selfVisits.agent',
				'selfVisits.status',
				'selfVisits.attachments',
			])->find($trip_id);

			if (!$trip) {
				$this->data['success'] = false;
				$this->data['message'] = 'Trip not found';
			}
			$travel_cities = Visit::leftjoin('ncities as cities', 'visits.to_city_id', 'cities.id')
				->where('visits.trip_id', $trip->id)->pluck('cities.name')->toArray();

			$transport_total = Visit::select(
				DB::raw('COALESCE(SUM(visit_bookings.amount), 0.00) as visit_amount'),
				DB::raw('COALESCE(SUM(visit_bookings.tax), 0.00) as visit_tax')
			)
				->leftjoin('visit_bookings', 'visit_bookings.visit_id', 'visits.id')
				->where('visits.trip_id', $trip_id)
				->groupby('visits.id')
				->get()
				->toArray();
			$visit_amounts = array_column($transport_total, 'visit_amount');
			$visit_taxes = array_column($transport_total, 'visit_tax');
			$visit_amounts_total = array_sum($visit_amounts);
			$visit_taxes_total = array_sum($visit_taxes);

			$transport_total_amount = $visit_amounts_total ? $visit_amounts_total : 0.00;
			$transport_total_tax = $visit_taxes_total ? $visit_taxes_total : 0.00;
			$this->data['transport_total_amount'] = number_format($transport_total_amount, 2, '.', '');

			$lodging_total = Lodging::select(
				DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
				DB::raw('COALESCE(SUM(tax), 0.00) as tax')
			)
				->where('trip_id', $trip_id)
				->groupby('trip_id')
				->first();
			$lodging_total_amount = $lodging_total ? $lodging_total->amount : 0.00;
			$lodging_total_tax = $lodging_total ? $lodging_total->tax : 0.00;
			$this->data['lodging_total_amount'] = number_format($lodging_total_amount, 2, '.', '');

			$boardings_total = Boarding::select(
				DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
				DB::raw('COALESCE(SUM(tax), 0.00) as tax')
			)
				->where('trip_id', $trip_id)
				->groupby('trip_id')
				->first();
			$boardings_total_amount = $boardings_total ? $boardings_total->amount : 0.00;
			$boardings_total_tax = $boardings_total ? $boardings_total->tax : 0.00;
			$this->data['boardings_total_amount'] = number_format($boardings_total_amount, 2, '.', '');

			$local_travels_total = LocalTravel::select(
				DB::raw('COALESCE(SUM(amount), 0.00) as amount'),
				DB::raw('COALESCE(SUM(tax), 0.00) as tax')
			)
				->where('trip_id', $trip_id)
				->groupby('trip_id')
				->first();
			$local_travels_total_amount = $local_travels_total ? $local_travels_total->amount : 0.00;
			$local_travels_total_tax = $local_travels_total ? $local_travels_total->tax : 0.00;
			$this->data['local_travels_total_amount'] = number_format($local_travels_total_amount, 2, '.', '');

			$total_amount = $transport_total_amount + $transport_total_tax + $lodging_total_amount + $lodging_total_tax + $boardings_total_amount + $boardings_total_tax + $local_travels_total_amount + $local_travels_total_tax;
			$this->data['total_amount'] = number_format($total_amount, 2, '.', '');
			$this->data['travel_cities'] = !empty($travel_cities) ? trim(implode(', ', $travel_cities)) : '--';
			$this->data['travel_dates'] = $travel_dates = Visit::select(DB::raw('MAX(DATE_FORMAT(visits.arrival_date,"%d/%m/%Y")) as max_date'), DB::raw('MIN(DATE_FORMAT(visits.departure_date,"%d/%m/%Y")) as min_date'))->where('visits.trip_id', $trip->id)->first();

			$this->data['trip_claim_rejection_list'] = collect(Entity::trip_claim_rejection()->prepend(['id' => '', 'name' => 'Select Rejection Reason']));
			$this->data['success'] = true;
		}
		$this->data['trip'] = $trip;

		return response()->json($this->data);
	}

	public function approveTripClaimVerificationTwo($trip_id) {

		$trip = Trip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim = EmployeeClaim::where('trip_id', $trip_id)->first();
		if (!$employee_claim) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$employee_claim->status_id = 3223; //Payment Pending
		$employee_claim->save();
		$trip->status_id = 3025; //Payment Pending
		$trip->save();
		return response()->json(['success' => true]);
	}

	public function rejectTripClaimVerificationTwo(Request $r) {

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

		return response()->json(['success' => true]);
	}

}
