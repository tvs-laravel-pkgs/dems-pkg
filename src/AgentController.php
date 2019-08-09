<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Validator;
use Yajra\Datatables\Datatables;

class AgentController extends Controller {
	public function listEYatraAgent(Request $r) {
		$agent_list = Agent::withTrashed()->select(
			'agents.id',
			'agents.code',
			'agents.name',
			'users.mobile_number',
			DB::raw('IF(agents.deleted_at IS NULL,"Active","In-Active") as status'),
			DB::raw('GROUP_CONCAT(tm.name) as travel_name'))
			->join('users', 'users.entity_id', 'agents.id')
			->leftJoin('agent_travel_mode', 'agent_travel_mode.agent_id', 'agents.id')
			->leftJoin('entities as tm', 'tm.id', 'agent_travel_mode.travel_mode_id')
			->where('users.user_type_id', 3122)
			->where('agents.company_id', Auth::user()->company_id)
			->groupby('agent_travel_mode.agent_id')
			->orderby('agents.id', 'desc');

		return Datatables::of($agent_list)
			->addColumn('action', function ($agent) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/agent/edit/' . $agent->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/agent/view/' . $agent->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_emp"
				onclick="angular.element(this).scope().deleteAgent(' . $agent->id . ')" dusk = "delete-btn" title="Delete">
		              <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
		              </a>';

			})
			->make(true);
	}

	public function eyatraAgentFormData($agent_id = NULL) {
		if (!$agent_id) {
			$this->data['action'] = 'New';
			$agent = new Agent;
			$address = new Address;
			$user = new User;
			$this->data['success'] = true;
			$this->data['travel_list'] = [];
		} else {
			$this->data['action'] = 'Edit';
			$agent = Agent::with('address', 'address.city', 'address.city.state')->find($agent_id);

			$user = User::where('entity_id', $agent_id)->where('user_type_id', 3122)->first();
			if (!$agent) {
				$this->data['success'] = false;
				$this->data['message'] = 'Agent not found';
			} else {
				$this->data['success'] = true;
			}
			$this->data['travel_list'] = $agent->travelModes()->where('company_id', Auth::user()->company_id)->pluck('travel_mode_id')->toArray();
		}

		$this->data['extras'] = [
			'travel_mode_list' => Entity::travelModeList(),
			'country_list' => NCountry::getList(),
			'state_list' => $this->data['action'] == 'New' ? [] : NState::getList($agent->address->city->state->country_id),
			'city_list' => $this->data['action'] == 'New' ? [] : NCity::getList($agent->address->city->state_id),
		];
		$this->data['agent'] = $agent;
		$this->data['address'] = $agent->address;
		$this->data['user'] = $user;

		return response()->json($this->data);
	}

	public function saveEYatraAgent(Request $request) {
		// dd($request->all());
		try {
			if (empty(count($request->travel_mode))) {
				return response()->json(['success' => false, 'errors' => ['Travel Mode is Required']]);
			}
			$error_messages = [
				'agent_code.required' => 'Agent Code is Required',
				'agent_name.required' => 'Agent Name is Required',
				'address_line1.required' => 'Address Line1 is Required',
				'country.required' => 'Country is Required',
				'state.required' => 'State is Required',
				'city.required' => 'City is Required',
				'pincode.required' => 'Pincode is Required',
				'username.required' => "User Name is Required",
				'password.required' => "Password is Required",
				'mobile_number.required' => "Mobile Number is Required",
			];

			$validator = Validator::make($request->all(), [
				'agent_code' => 'required',
				'agent_name' => 'required',
				'address_line1' => 'required',
				'country' => 'required',
				'state' => 'required',
				'city' => 'required',
				'pincode' => 'required',
				'mobile_number' => 'required',
				'password' => 'required',
				'username' => 'required',
			]);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			$company_id = Auth::user()->company_id;
			if (!$request->id) {
				$agent = new Agent;
				$user = new User;
				$address = new Address;
				$agent->created_by = Auth::user()->id;
				$agent->created_at = Carbon::now();
				$agent->updated_at = NULL;
			} else {
				$agent = Agent::find($request->id);
				$user = User::where('entity_id', $request->id)->where('user_type_id', 5122)->first();
				$address = Address::where('entity_id', $request->id)->first();
				$agent->updated_by = Auth::user()->id;
				$agent->updated_at = Carbon::now();
			}
			$agent->company_id = $company_id;
			$agent->code = $request->agent_code;
			$agent->name = $request->agent_name;
			$agent->fill($request->all());
			$agent->save();

			//ADD ADDRESS
			$address->address_of_id = 3161;
			$address->entity_id = $agent->id;
			$address->name = 'Primary';
			$address->line_1 = $request->address_line1;
			$address->line_2 = $request->address_line2;
			$address->city_id = $request->city;
			$address->fill($request->all());
			$address->save();

			//ADD USER
			$user->mobile_number = $request->mobile_number;
			$user->entity_type = 0;
			$user->user_type_id = 3122;
			$user->company_id = $company_id;
			$user->entity_id = $agent->id;
			$user->fill($request->all());
			$user->save();

			$agent->travelModes()->sync($request->travel_mode);

			DB::commit();
			$request->session()->flash('success', 'Agent saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Agent Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Agent Updated Successfully']);
			}
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraAgent($agent_id) {
		dd($agent_id);
		$trip = Agent::with([
			'agents',
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
			->find($agent_id);
		if (!$trip) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Agent not found'];
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

	public function deleteEYatraAgent($agent_id) {
		$agent = Agent::where('id', $agent_id)->delete();
		if (!$agent) {
			return response()->json(['success' => false, 'errors' => ['Agent not found']]);
		}
		return response()->json(['success' => true]);
	}

}
