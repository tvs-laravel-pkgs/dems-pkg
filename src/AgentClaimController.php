<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Storage;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\AgentClaim;
use Uitoux\EYatra\VisitBooking;
use Validator;
use Yajra\Datatables\Datatables;

class AgentClaimController extends Controller {
	public function listEYatraAgentClaimList(Request $r) {

		$agent_claim_list = Agentclaim::select(
			'ey_agent_claims.id',
			'ey_agent_claims.number',
			'ey_agent_claims.status_id as claim_status',
			'ey_agent_claims.invoice_date',
			'ey_agent_claims.invoice_number',
			'ey_agent_claims.invoice_amount',
			'agents.code as agent_code',
			'agents.name as agent_name',
			'configs.name as status',
			DB::raw('DATE_FORMAT(ey_agent_claims.created_at,"%d/%m/%Y") as date'))
			->leftJoin('agents', 'agents.id', 'ey_agent_claims.agent_id')
			->leftJoin('configs', 'configs.id', 'ey_agent_claims.status_id')
			->where('ey_agent_claims.agent_id', Auth::user()->entity_id)
			->orderBy('ey_agent_claims.id', 'desc');
		// ->get();

		return Datatables::of($agent_claim_list)
			->addColumn('action', function ($agent_claim_list) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');

				if ($agent_claim_list->claim_status == '3024') {
					return '
						<a href="#!/eyatra/agent/claim/edit/' . $agent_claim_list->id . '">
							<img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '" >
						</a>
						<a href="#!/eyatra/agent/claim/view/' . $agent_claim_list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>
						';
				} else {
					return '
						<a href="#!/eyatra/agent/claim/view/' . $agent_claim_list->id . '">
							<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
						</a>';
				}

			})
			->make(true);
	}

	public function eyatraAgentClaimFormData($agent_claim_id = NULL) {
		dd($agent_claim_id);

		if ($agent_claim_id) {

		} else {
			$this->data['action'] = 'New';
			$agent_claim = new AgentClaim;
			$this->data['success'] = true;
			$this->data['attachment'] = [];

			$this->data['booking_list'] = $booking_list = VisitBooking::select(DB::raw('SUM(visit_bookings.paid_amount)'), 'trips.id as trip_id', 'visits.id as visit_id', 'visit_bookings.paid_amount', 'employees.code as employee_code',
				'employees.name as employee_name', 'configs.name as status')
				->join('visits', 'visits.id', 'visit_bookings.visit_id')
				->join('trips', 'trips.id', 'visits.trip_id')
				->join('employees', 'employees.id', 'trips.employee_id')
				->join('configs', 'configs.id', 'trips.status_id')
				->where('visit_bookings.created_by', Auth::user()->id)
				->where('visit_bookings.status_id', 3240)
				->groupBy('trips.id')
				->get();
		}

		// $this->data['booking_list'] = $booking_list = VisitBooking::select(
		// 	'visit_bookings.id',
		// 	'type.name as type_id',
		// 	'trips.number as trip',
		// 	'employees.code as employee_code',
		// 	'employees.name as employee_name',
		// 	'visit_bookings.amount',
		// 	'visit_bookings.tax',
		// 	'visit_bookings.service_charge',
		// 	'visit_bookings.total',
		// 	'visit_bookings.paid_amount',
		// 	'visit_bookings.reference_number',
		// 	'from_city.name as from',
		// 	'to_city.name as to',
		// 	'travel_mode.name as travel_mode'
		// )
		// 	->leftJoin('configs as type', 'type.id', 'visit_bookings.type_id')
		// 	->leftJoin('visits', 'visits.id', 'visit_bookings.visit_id')
		// 	->leftJoin('trips', 'trips.id', 'visits.trip_id')
		// 	->leftJoin('employees', 'employees.id', 'trips.employee_id')
		// 	->leftJoin('ncities as from_city', 'from_city.id', 'visits.from_city_id')
		// 	->leftJoin('ncities as to_city', 'to_city.id', 'visits.to_city_id')
		// 	->leftJoin('entities as travel_mode', 'travel_mode.id', 'visit_bookings.travel_mode_id')
		// 	->where('visit_bookings.created_by', Auth::user()->id)
		// 	->where('visit_bookings.status_id', 3240)
		// 	->get();
		$date = '';

		$this->data['gstin_tax'] = Agent::select('gstin')->where('id', Auth::user()->entity_id)->get();
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
		// dd($request->booking_list);
		//validation
		try {
			$error_messages = [
				'invoice_number.required' => 'Invoice Number is Required',
				'invoice_number.unique' => 'Invoice Number is already taken',
				'date.required' => 'Invoice Date is Required',
				'net_amount.required' => 'Net Amount is Required',
				// 'tax.required' => 'Tax is Required',
				// 'invoice_amount.required' => 'Invoice Amount is Required',
				'booking_list.*.required' => 'Booking List is Required',
			];
			$validator = Validator::make($request->all(), [
				'invoice_number' => [
					'required:true',
					'unique:ey_agent_claims,invoice_number,' . $request->id . ',id,agent_id,' . Auth::user()->id,
				],
				'date' => "required",
				'net_amount' => "required",
				// 'tax' => "required",
				// 'invoice_amount' => "required",
				'booking_list.*' => "required",
			]);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			if ($request->tax == '') {
				$request->tax = 0;
			}

			if (!empty($request->booking_list)) {
				if (!array_filter($request->booking_list)) {
					return response()->json(['success' => false, 'errors' => ['Select Booking List!']]);
				} elseif ($request->booking_list == '') {
					return response()->json(['success' => false, 'errors' => ['Booking List is Empty!']]);
				}
			} else {
				return response()->json(['success' => false, 'errors' => ['Booking List is Empty!']]);
			}
			$invoice_date = date("Y-m-d", strtotime($request->date));
			// dd($request->all());

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
			}
			// $agentClaim->number = 'INVOICE_' . rand();
			$agentClaim->agent_id = Auth::user()->entity_id;
			$agentClaim->invoice_date = $invoice_date;
			$agentClaim->net_amount = $request->net_amount;
			$agentClaim->tax = $request->tax;
			// dd($invoice_amount);
			$agentClaim->invoice_amount = $request->invoice_amount;
			$agentClaim->status_id = 3222;
			$agentClaim->fill($request->all());
			if ($request->invoice_amount == '' || $request->invoice_amount == null) {
				$agentClaim->invoice_amount = $request->net_amount;
			}

			$agentClaim->save();
			$agentClaim->number = 'INVOICE_' . $agentClaim->id;
			$agentClaim->save();

			// dd($request->booking_list);
			//UPDATE VISIT BOOKING BY AGENT
			$visit_book = VisitBooking::join('visits', 'visits.id', 'visit_bookings.visit_id')
				->whereIn('visits.trip_id', $request->booking_list)
				->update(['visit_bookings.status_id' => 3222, 'visit_bookings.agent_claim_id' => $agentClaim->id]);
			// $booking_list_array = implode(',', $request->booking_list);
			// $visit_book = VisitBooking::whereIn('id', $request->booking_list)->update(['status_id' => 3222, 'agent_claim_id' => $agentClaim->id]);

			//STORE ATTACHMENT
			$item_images = 'agent_claim/attachments/';
			Storage::makeDirectory($item_images, 0777);
			if (!empty($request->invoice_attachmet)) {
				$attachement = $request->invoice_attachmet;
				$name = $attachement->getClientOriginalName();
				$attachement->move(storage_path('app/public/agent_claim/attachments/'), $name);
				$attachement_vendor_claim = new Attachment;
				$attachement_vendor_claim->attachment_of_id = 3161; //agent id from configs
				$attachement_vendor_claim->attachment_type_id = 3200;
				$attachement_vendor_claim->entity_id = $agentClaim->id;
				$attachement_vendor_claim->name = $name;
				$attachement_vendor_claim->save();
			}

			// $agentClaim->bookings()->sync($request->booking_list);

			DB::commit();
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

	public function viewEYatraAgentClaim($agent_claim_id) {

		$this->data['agent_claim_view'] = $agent_claim_view = Agentclaim::join('agents', 'agents.id', 'ey_agent_claims.agent_id')->select(
			'ey_agent_claims.id',
			'ey_agent_claims.invoice_number',
			'ey_agent_claims.net_amount',
			'ey_agent_claims.tax',
			'agents.name as agent_name', 'agents.id as agent_id', 'agents.code as agent_code',
			DB::raw('DATE_FORMAT(ey_agent_claims.invoice_date,"%d/%m/%Y") as invoice_date'),
			'ey_agent_claims.invoice_amount')
			->where('ey_agent_claims.id', $agent_claim_id)->first();

		$this->data['booking_list'] = $booking_list = VisitBooking::select(DB::raw('SUM(visit_bookings.paid_amount)'),
			'visit_bookings.id',
			'visit_bookings.paid_amount',
			'configs.name as status',
			'trips.number as trip',
			'trips.id as trip_id',
			'employees.code as employee_code',
			'employees.name as employee_name')
			->leftJoin('visits', 'visits.id', 'visit_bookings.visit_id')
			->leftJoin('trips', 'trips.id', 'visits.trip_id')
			->leftJoin('employees', 'employees.id', 'trips.employee_id')
			->join('configs', 'configs.id', 'trips.status_id')
			->where('visit_bookings.agent_claim_id', $agent_claim_id)
			->groupBy('trips.id')
			->get();
		$this->data['total_trips'] = count($booking_list);
		$this->data['success'] = true;
		$this->data['gstin_tax'] = Agent::select('gstin')->where('id', Auth::user()->entity_id)->get();
		return response()->json($this->data);
	}

	public function deleteEYatraAgentClaim($agent_claim_id) {
		$agent_claim = Agentclaim::where('id', $agent_claim_id)->forceDelete();
		if (!$agent_claim) {
			return response()->json(['success' => false, 'errors' => ['Agent Claim not found']]);
		}
		return response()->json(['success' => true]);
	}

	public function listFinanceEYatraAgentClaimList(Request $r) {

		$agent_claim_list = Agentclaim::select(
			'ey_agent_claims.id',
			'ey_agent_claims.number',
			'ey_agent_claims.invoice_date',
			'ey_agent_claims.invoice_number',
			'ey_agent_claims.invoice_amount',
			'agents.code as agent_code',
			'agents.name as agent_name',
			'configs.name as status',
			DB::raw('DATE_FORMAT(ey_agent_claims.created_at,"%d/%m/%Y") as date'))
			->leftJoin('agents', 'agents.id', 'ey_agent_claims.agent_id')
			->leftJoin('configs', 'configs.id', 'ey_agent_claims.status_id')
		// ->where('ey_agent_claims.agent_id', Auth::user()->entity_id)
			->orderBy('ey_agent_claims.id', 'desc');
		// ->get();

		return Datatables::of($agent_claim_list)
			->addColumn('action', function ($agent_claim_list) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/finance/agent/claim/view/' . $agent_claim_list->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>';

			})
			->make(true);
	}

	public function viewEYatraFinanceAgentClaim($agent_claim_id) {
		$this->data['agent_claim_view'] = $agent_claim_view = Agentclaim::join('agents', 'agents.id', 'ey_agent_claims.agent_id')
			->join('configs', 'configs.id', 'ey_agent_claims.status_id')->select(
			'ey_agent_claims.id',
			'ey_agent_claims.invoice_number',
			'ey_agent_claims.net_amount',
			'configs.name as status',
			'ey_agent_claims.tax', 'ey_agent_claims.status_id',
			'agents.name as agent_name', 'agents.id as agent_id', 'agents.code as agent_code',
			DB::raw('DATE_FORMAT(ey_agent_claims.invoice_date,"%d/%m/%Y") as invoice_date'),
			'ey_agent_claims.invoice_amount')
			->where('ey_agent_claims.id', $agent_claim_id)->first();

		$this->data['booking_list'] = $booking_list = VisitBooking::select(DB::raw('SUM(visit_bookings.paid_amount)'),
			'visit_bookings.id',
			'visit_bookings.paid_amount',
			'configs.name as status',
			'trips.number as trip',
			'trips.id as trip_id',
			'employees.code as employee_code',
			'employees.name as employee_name')
			->leftJoin('visits', 'visits.id', 'visit_bookings.visit_id')
			->leftJoin('trips', 'trips.id', 'visits.trip_id')
			->leftJoin('employees', 'employees.id', 'trips.employee_id')
			->join('configs', 'configs.id', 'trips.status_id')
			->where('visit_bookings.agent_claim_id', $agent_claim_id)
			->groupBy('trips.id')
			->get();
		$this->data['total_trips'] = count($booking_list);
		$this->data['success'] = true;
		$this->data['gstin_tax'] = Agent::select('gstin')->where('id', Auth::user()->entity_id)->get();

		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);

		$agent = Agent::withTrashed()->with('bankDetail', 'walletDetail', 'address', 'address.city', 'address.city.state')->find($agent_claim_view->agent_id);

		$this->data['agent'] = $agent;
		$this->data['payment_mode_list'] = $payment_mode_list;
		$this->data['wallet_mode_list'] = $wallet_mode_list;
		$this->data['agent_claim_rejection'] = $agent_claim_rejection = Entity::agent_claim_rejection();
		$this->data['date'] = date('d-m-Y');
		return response()->json($this->data);
	}
	public function payAgentClaimRequest(Request $r) {

		// dd($r->all());
		$agent_claim = AgentClaim::find($r->agent_claim_id);
		if (!$agent_claim) {
			return response()->json(['success' => false, 'errors' => ['Agent Claim Request not found']]);
		}
		$agent_claim->status_id = 3243;
		$agent_claim->save();

		//PAYMENT SAVE
		$payment = Payment::firstOrNew(['entity_id' => $agent_claim->id]);
		$payment->date = date('Y-m-d', strtotime($r->date));
		$payment->fill($r->all());
		$payment->payment_of_id = 3252;
		$payment->entity_id = $agent_claim->id;
		$payment->created_by = Auth::user()->id;
		$payment->save();

		//BANK DETAIL SAVE
		if ($r->bank_name) {
			$bank_detail = BankDetail::firstOrNew(['entity_id' => $agent_claim->id]);
			$bank_detail->fill($r->all());
			$bank_detail->detail_of_id = 3243;
			$bank_detail->entity_id = $agent_claim->id;
			$bank_detail->account_type_id = 3252;
			$bank_detail->save();
		}

		//WALLET SAVE
		if ($r->type_id) {
			$wallet_detail = WalletDetail::firstOrNew(['entity_id' => $agent_claim->id]);
			$wallet_detail->fill($r->all());
			$wallet_detail->wallet_of_id = 3252;
			$wallet_detail->entity_id = $agent_claim->id;
			$wallet_detail->save();
		}

		// $trip->visits()->update(['manager_verification_status_id' => 3080]);
		return response()->json(['success' => true]);
	}
	public function rejectAgentClaimRequest(Request $r) {

		$agent_claim = AgentClaim::find($r->agent_claim_view_id);
		if (!$agent_claim) {
			return response()->json(['success' => false, 'errors' => ['Agent Claim Request not found']]);
		}
		$agent_claim->rejection_id = $r->reject_id;
		$agent_claim->rejection_remarks = $r->remarks;
		$agent_claim->status_id = 3024;
		$agent_claim->save();

		return response()->json(['success' => true]);
	}

	// 3252

}
