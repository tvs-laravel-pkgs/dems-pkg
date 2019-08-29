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

	public function eyatraAgentsfilter() {

		$option = new Entity;
		$option->name = 'Select Travel Mode';
		$option->id = NULL;
		$this->data['tm_list'] = $tm_list = Entity::select('name', 'id')->where('entity_type_id', 502)->where('company_id', Auth::user()->company_id)->get()->keyBy('id');

		$this->data['tm_list'] = $tm_list->prepend($option);

		$this->data['status_list'] = array(
			array('name' => "Select Status", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Active", 'id' => "2"),
			array('name' => "Inactive", 'id' => "1"),
		);
		return response()->json($this->data);
	}

	public function listEYatraAgent(Request $r) {

		if (!empty($r->agent)) {
			$agent = $r->agent;
		} else {
			$agent = null;
		}

		if (!empty($r->tm)) {
			$tm = $r->tm;
		} else {
			$tm = null;
		}
		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;
		}

		$agent_list = Agent::withTrashed()->select(
			'agents.id',
			'agents.code',
			'users.name',
			DB::raw('IF(agents.gstin IS NULL,"---",agents.gstin) as gstin'),
			'users.mobile_number',
			DB::raw('IF(agents.deleted_at IS NULL,"Active","In-Active") as status'),
			DB::raw('GROUP_CONCAT(tm.name) as travel_name'))
			->leftJoin('users', 'users.entity_id', 'agents.id')
			->leftJoin('agent_travel_mode', 'agent_travel_mode.agent_id', 'agents.id')
			->leftJoin('entities as tm', 'tm.id', 'agent_travel_mode.travel_mode_id')
			->where('users.user_type_id', 3122)
			->where(function ($query) use ($r, $agent) {
				if (!empty($agent)) {
					$query->where('agents.id', $agent);
				}
			})
			->where(function ($query) use ($r, $tm) {
				if (!empty($tm)) {
					$query->where('tm.id', $tm);
				}
			})
			->where(function ($query) use ($r, $status) {
				if ($status == '2') {
					$query->whereNull('agents.deleted_at');
				} elseif ($status == '1') {
					$query->whereNotNull('agents.deleted_at');
				}
			})
			->where('agents.company_id', Auth::user()->company_id)
			->groupby('agent_travel_mode.agent_id')
			->orderby('agents.id', 'desc')
		;
		return Datatables::of($agent_list)
			->addColumn('action', function ($agent) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/agent/edit/' . $agent->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/agent/view/' . $agent->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#agent_confirm_box"
				onclick="angular.element(this).scope().deleteAgentConfirm(' . $agent->id . ')" dusk = "delete-btn" title="Delete">
		              <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
		              </a>';

			})
			->addColumn('status', function ($agent) {
				if ($agent->status == 'In-Active') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}
			})
			->make(true);
	}

	public function eyatraAgentFormData($agent_id = NULL) {
		//dd($agent_id);
		if (!$agent_id) {
			$this->data['action'] = 'Add';
			$agent = new Agent;
			$address = new Address;
			$user = new User;
			$user->password_change = 'yes';
			$this->data['success'] = true;
			$this->data['travel_list'] = [];
		} else {
			$this->data['action'] = 'Edit';
			$agent = Agent::withTrashed()->with('bankDetail', 'walletDetail', 'address', 'address.city', 'address.city.state', 'user')->find($agent_id);

			$user = User::where('entity_id', $agent_id)->where('user_type_id', 3122)->first();
			// dd($user);
			if (!$agent) {
				$this->data['success'] = false;
				$this->data['message'] = 'Agent not found';
			} else {
				$this->data['success'] = true;
			}
			$this->data['travel_list'] = $agent->travelModes()->pluck('travel_mode_id')->toArray();
		}

		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);

		$this->data['extras'] = [
			'travel_mode_list' => Entity::travelModeList(),
			'country_list' => NCountry::getList(),

			'state_list' => $this->data['action'] == 'Add' ? [] : NState::getList($agent->address->city->state->country_id),
			'city_list' => $this->data['action'] == 'Add' ? [] : NCity::getList($agent->address->city->state_id),
			'payment_mode_list' => $payment_mode_list,
			'wallet_mode_list' => $wallet_mode_list,
		];
		//dd($agent);
		$this->data['agent'] = $agent;
		$this->data['address'] = $agent->address;
		$this->data['user'] = $user;

		return response()->json($this->data);
	}

	public function saveEYatraAgent(Request $request) {
		//dd($request->all());
		try {
			if (empty(count($request->travel_mode))) {
				return response()->json(['success' => false, 'errors' => ['Travel Mode is Required']]);
			}
			$error_messages = [
				'agent_code.required' => 'Agent Code is Required',
				'agent_code.unique' => 'Agent Code is already taken',
				'agent_name.required' => 'Agent Name is Required',
				'address_line1.required' => 'Address Line1 is Required',
				'country.required' => 'Country is Required',
				'state.required' => 'State is Required',
				'city_id.required' => 'City is Required',
				'pincode.required' => 'Pincode is Required',
				'username.required' => "User Name is Required",
				'mobile_number.required' => "Mobile Number is Required",
				'mobile_number.unique' => "Mobile Number is already taken",
			];

			$validator = Validator::make($request->all(), [
				'agent_code' => [
					'required:true',
					'unique:agents,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'mobile_number' => [
					'required:true',
					'unique:users,mobile_number,' . $request->user_id . ',id,company_id,' . Auth::user()->company_id,
				],
				'agent_name' => 'required',
				'address_line1' => 'required',
				'country' => 'required',
				'state' => 'required',
				'city_id' => 'required',
				'pincode' => 'required',
				'username' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			if ($request->password_change == 'Yes' && $request->password == '') {
				return response()->json(['success' => false, 'errors' => ['Password is Required']]);
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
				$agent = Agent::withTrashed()->find($request->id);
				$user = User::where('id', $request->user_id)->first();
				$address = Address::where('entity_id', $request->id)->first();
				$agent->updated_by = Auth::user()->id;
				$agent->updated_at = Carbon::now();
			}
			$agent->company_id = $company_id;
			$agent->code = $request->agent_code;
			$agent->fill($request->all());
			if ($request->status == 'Active') {
				$agent->deleted_by = NULL;
				$agent->deleted_at = NULL;
			} else if ($request->status == 'Inactive') {
				$agent->deleted_by = Auth()->user()->id;
				$agent->deleted_at = Carbon::now();
			}
			$agent->save();

			//ADD ADDRESS
			$address->address_of_id = 3161;
			$address->entity_id = $agent->id;
			$address->name = 'Primary';
			$address->line_1 = $request->address_line1;
			$address->line_2 = $request->address_line2;

			// $address->country_id = $request->country;
			// $address->city_id = $request->city;
			$address->fill($request->all());
			$address->save();

			//ADD USER
			$user->mobile_number = $request->mobile_number;
			$user->name = $request->agent_name;
			$user->entity_type = 0;
			$user->user_type_id = 3122;
			$user->company_id = $company_id;
			$user->name = $request->agent_name;
			$user->entity_id = $agent->id;
			$user->fill($request->all());
			if ($request->password_change == 'Yes') {
				if (!empty($request->user['password'])) {
					$user->password = $request->user['password'];
				}
				$user->force_password_change = 1;
			}

			// $user->fill($request->all());
			$user->save();

			$user->roles()->sync([503]);

			$agent->travelModes()->sync($request->travel_mode);

			//BANK DETAIL SAVE
			if ($request->bank_name) {
				$bank_detail = BankDetail::firstOrNew(['entity_id' => $agent->id]);
				$bank_detail->fill($request->all());
				$bank_detail->detail_of_id = 3243;
				$bank_detail->entity_id = $agent->id;
				$bank_detail->account_type_id = 3243;
				$bank_detail->save();
			}

			//WALLET SAVE
			if ($request->type_id) {
				$wallet_detail = WalletDetail::firstOrNew(['entity_id' => $agent->id]);
				$wallet_detail->fill($request->all());
				$wallet_detail->wallet_of_id = 3243;
				$wallet_detail->save();
			}

			DB::commit();
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

		// $this->data['agent'] = $agent = Agent::find($agent_id);
		$this->data['agent'] = $agent = Agent::withTrashed()->with('bankDetail', 'walletDetail', 'address', 'address.city', 'address.city.state')->find($agent_id);
		$this->data['address'] = $address = Address::join('ncities', 'ncities.id', 'ey_addresses.city_id')
			->join('nstates', 'nstates.id', 'ncities.state_id')
			->join('country as c', 'c.id', 'nstates.country_id')
			->where('entity_id', $agent_id)->where('address_of_id', 3161)
			->select('ey_addresses.*', 'ncities.name as city_name', 'nstates.name as state_name', 'c.name as country_name')
			->first();

		$this->data['user_details'] = $user = User::where('entity_id', $agent_id)->where('user_type_id', 3122)->first();

		$this->data['travel_list'] = $agent->travelModes;

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraAgent($agent_id) {
		$agent = Agent::where('id', $agent_id)->forceDelete();
		if (!$agent) {
			return response()->json(['success' => false, 'errors' => ['Agent not found']]);
		}
		return response()->json(['success' => true]);
	}

}
