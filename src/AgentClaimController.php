<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\AgentClaim;
use Uitoux\EYatra\VisitBooking;
use Validator;
use Yajra\Datatables\Datatables;

class AgentClaimController extends Controller {
	public function listEYatraAgentClaimList(Request $r) {

		$agent_claim_list = Agentclaim::select(
			'ey_agent_claims.id',
			'ey_agent_claims.number',
			'ey_agent_claims.invoice_date',
			'ey_agent_claims.invoice_number',
			'ey_agent_claims.invoice_amount',
			'agents.code as agent_code',
			'configs.name as status',
			DB::raw('DATE_FORMAT(ey_agent_claims.created_at,"%d/%m/%Y") as date'))
			->leftJoin('agents', 'agents.id', 'ey_agent_claims.agent_id')
			->leftJoin('configs', 'configs.id', 'ey_agent_claims.status_id')
			->where('ey_agent_claims.agent_id', Auth::user()->entity_id)
			->orderBy('ey_agent_claims.id', 'desc')->get();
		// dd($agent_claim_list);

		return Datatables::of($agent_claim_list)
			->addColumn('action', function ($agent_claim_list) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/agent/claim/edit/' . $agent_claim_list->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/agent/claim/view/' . $agent_claim_list->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>

				<a href="javascript:;" data-toggle="modal" data-target="#agent_claim"
				onclick="angular.element(this).scope().deleteAgentClaimconfirm(' . $agent_claim_list->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
				</a>';

			})
			->make(true);
	}

	public function eyatraAgentClaimFormData($agent_claim_id = NULL) {
		//dd($agent_claim_id);
		if (!$agent_claim_id) {
			$this->data['action'] = 'New';
			$agent_claim = new AgentClaim;
			$this->data['success'] = true;
			$this->data['booking_pivot'] = [];
			$date = '';
		} else {
			$this->data['action'] = 'Edit';
			$agent_claim = Agentclaim::find($agent_claim_id);
			$date = date("d-m-Y", strtotime($agent_claim->invoice_date));
			if (!$agent_claim) {
				$this->data['success'] = false;
				$this->data['message'] = 'Agent Claim not found';
			} else {
				$this->data['success'] = true;
			}
			$this->data['booking_pivot'] = $agent_claim->bookings()->pluck('booking_id')->toArray();
		}
		$this->data['booking_list'] = $booking_list = VisitBooking::select(
			'visit_bookings.id',
			'type.name as type_id',
			'trips.number as trip',
			'employees.code as employee_code',
			'visit_bookings.amount',
			'visit_bookings.tax',
			'visit_bookings.service_charge',
			'visit_bookings.total',
			'visit_bookings.reference_number',
			'from_city.name as from',
			'to_city.name as to',
			'travel_mode.name as travel_mode'
		)
			->leftJoin('configs as type', 'type.id', 'visit_bookings.type_id')
			->leftJoin('visits', 'visits.id', 'visit_bookings.visit_id')
			->leftJoin('trips', 'trips.id', 'visits.trip_id')
			->leftJoin('employees', 'employees.id', 'trips.employee_id')
			->leftJoin('ncities as from_city', 'from_city.id', 'visits.from_city_id')
			->leftJoin('ncities as to_city', 'to_city.id', 'visits.to_city_id')
			->leftJoin('entities as travel_mode', 'travel_mode.id', 'visit_bookings.travel_mode_id')
			->where('visit_bookings.created_by', Auth::user()->id)
			->get();
		// $this->data['extras'] = [
		// 	'manager_list' => Employee::getList(),
		// 	'outlet_list' => Outlet::getList(),
		// 	'grade_list' => Entity::getGradeList(),
		// ];
		$this->data['agent_claim'] = $agent_claim;
		$this->data['invoice_date'] = $date;

		return response()->json($this->data);
	}

	public function saveEYatraAgentClaim(Request $request) {
		// dd($request->all());
		//validation
		try {
			$error_messages = [
				'invoice_number.required' => 'Invoice Number is Required',
				'invoice_number.unique' => 'Invoice Number is already taken',
				'date.required' => 'Invoice Date is Required',
				'amount.required' => 'Invoice Amount is Required',
				'booking_list.*.required' => 'Booking List is Required',
			];
			$validator = Validator::make($request->all(), [
				'invoice_number' => [
					'required:true',
					'unique:ey_agent_claims,invoice_number,' . $request->id . ',id,agent_id,' . Auth::user()->id,
				],
				'date' => "required",
				'amount' => "required",
				'booking_list.*' => "required",
			]);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			if (!array_filter($request->booking_list)) {
				return response()->json(['success' => false, 'errors' => ['Select Booking List!']]);
			}
			$invoice_date = date("Y-m-d", strtotime($request->date));

			DB::beginTransaction();
			if (!$request->id) {
				$agentClaim = new Agentclaim;
				$agentClaim->created_by = Auth::user()->id;
				$agentClaim->created_at = Carbon::now();
				$agentClaim->updated_at = NULL;

			} else {
				$agentClaim = Agentclaim::find($request->id);
				$agentClaim->updated_by = Auth::user()->id;
				$agentClaim->updated_at = Carbon::now();
				// $agentClaim->visits()->sync([]);
			}
			//$agent_id = Auth::user()->entity_id;
			//dd(Auth::user()->where('user_type_id', 3122)->where('entity_id', $agent_id)->pluck('entity_id'));
			$agentClaim->number = 'INVOICE_' . rand();
			$agentClaim->agent_id = Auth::user()->entity_id;
			$agentClaim->invoice_date = $invoice_date;
			$agentClaim->invoice_amount = $request->amount;
			$agentClaim->status_id = 3222;
			$agentClaim->fill($request->all());
			$agentClaim->save();

			$agentClaim->bookings()->sync($request->booking_list);

			DB::commit();
			$request->session()->flash('success', 'Agent Claim Requested successfully!');
			return response()->json(['success' => true]);
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Agent Claim Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Agent Claim Updated Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraAgentClaim($employee_id) {

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
			$this->data['success'] = false;
			$this->data['errors'] = ['Trip not found'];
			return response()->json($this->data);
		}
		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $start_date->end_date;
		$this->data['trip'] = $trip;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraAgentClaim($agent_claim_id) {
		$agent_claim = Agentclaim::where('id', $agent_claim_id)->delete();
		if (!$agent_claim) {
			return response()->json(['success' => false, 'errors' => ['Agent Claim not found']]);
		}
		return response()->json(['success' => true]);
	}

}
