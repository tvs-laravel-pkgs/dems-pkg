<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Validator;
use Yajra\Datatables\Datatables;

class StateController extends Controller {

	public function filterEYatraState() {
		$option = new NCountry;
		$option->name = 'Select Country';
		$option->id = null;
		$this->data['country_list'] = $country_list = NCountry::select('name', 'id')->get()->prepend($option);
		$this->data['status_list'] = array(
			array('name' => "Select Status", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Active", 'id' => "2"),
			array('name' => "Inactive", 'id' => "1"),
		);
		return response()->json($this->data);
	}

	public function listEYatraState(Request $r) {
		if (!empty($r->country)) {
			$country = $r->country;
		} else {
			$country = null;
		}
		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;
		}
		$states = NState::withTrashed()->from('nstates')
			->join('country as c', 'c.id', 'nstates.country_id')
			->select(
				'nstates.id',
				'nstates.code',
				'nstates.name',
				'c.name as country',
				DB::raw('IF(nstates.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where(function ($query) use ($r, $country) {
				if (!empty($country)) {
					$query->where('c.id', $country);
				}
			})
			->where(function ($query) use ($r, $status) {
				if ($status == '2') {
					$query->whereNull('nstates.deleted_at');
				} elseif ($status == '1') {
					$query->whereNotNull('nstates.deleted_at');
				}
			})
			->orderBy('nstates.name', 'asc');

		return Datatables::of($states)
			->addColumn('action', function ($state) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/state/edit/' . $state->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/state/view/' . $state->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_state"
				onclick="angular.element(this).scope().deleteStateConfirm(' . $state->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($state) {
				if ($state->status == 'Inactive') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}

			})
			->make(true);
	}

	public function eyatraStateFormData($state_id = NULL) {
		if (!$state_id) {
			$this->data['action'] = 'Add';
			$state = new NState;
			$this->data['status'] = 'Active';

			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$state = NState::withTrashed()->find($state_id);

			if (!$state) {
				$this->data['success'] = false;
				$this->data['message'] = 'State not found';
			}

			if ($state->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		}
		$option = new NCountry;
		$option->name = 'Select Country';
		$option->id = null;
		$this->data['country_list'] = $country_list = NCountry::select('name', 'id')->get()->prepend($option);
		$this->data['travel_mode_list'] = $travel_modes = Entity::select('name', 'id')->where('entity_type_id', 502)->where('company_id', Auth::user()->company_id)->get()->keyBy('id');
		$option = new Agent;
		$option->name = 'Select Agent';
		$option->id = null;
		$this->data['agents_list'] = $agents_list = collect(Agent::select(DB::raw('CONCAT(users.name ," / ",agents.code) as name, agents.id'))
				->leftJoin('users', 'users.entity_id', 'agents.id')
				->where('users.user_type_id', 3122)
				->where('agents.company_id', Auth::user()->company_id)->get())->prepend($option);
		// dd($state->travelModes()->withPivot()->get());
		foreach ($state->travelModes->where('company_id', Auth::user()->company_id) as $travel_mode) {
			$this->data['travel_mode_list'][$travel_mode->id]->checked = true;
			$this->data['travel_mode_list'][$travel_mode->id]->agent_id = $travel_mode->pivot->agent_id;
			$this->data['travel_mode_list'][$travel_mode->id]->service_charge = $travel_mode->pivot->service_charge;
		}

		$this->data['state'] = $state;
		$this->data['success'] = true;

		return response()->json($this->data);
	}

	public function saveEYatraState(Request $request) {
		//validation
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'State Code is required',
				'code.unique' => 'State Code has already been taken',
				'name.required' => 'State Name is required',
				'name.unique' => 'State Name has already been taken',

			];

			$validator = Validator::make($request->all(), [
				'code' => [
					'required',
					'unique:nstates,code,' . $request->id . ',id,country_id,' . $request->country_id,
					'max:2',
				],
				'name' => [
					'required',
					'unique:nstates,name,' . $request->id . ',id,country_id,' . $request->country_id,
					'max:191',
				],

			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$state = new NState;
				$state->created_by = Auth::user()->id;
				$state->created_at = Carbon::now();
				$state->updated_at = NULL;

			} else {
				$state = NState::withTrashed()->where('id', $request->id)->first();

				$state->updated_by = Auth::user()->id;
				$state->updated_at = Carbon::now();

				$state->travelModes()->sync([]);
			}
			if ($request->status == 'Active') {
				$state->deleted_at = NULL;
				$state->deleted_by = NULL;
			} else {
				$state->deleted_at = date('Y-m-d H:i:s');
				$state->deleted_by = Auth::user()->id;

			}

			$state->fill($request->all());
			$state->save();

			//SAVING state_agent_travel_mode
			if (count($request->travel_modes) > 0) {
				foreach ($request->travel_modes as $travel_mode => $pivot_data) {
					if (!isset($pivot_data['agent_id'])) {
						continue;
					}
					if (!isset($pivot_data['service_charge'])) {
						continue;
					}
					$state->travelModes()->attach($travel_mode, $pivot_data);
				}
			}

			DB::commit();
			$request->session()->flash('success', 'State saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'State Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'State Updated Successfully']);
			}
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraState($state_id) {

		$state = NState::with([

			'country',
		])->select('*', DB::raw('IF(nstates.deleted_at IS NULL,"Active","Inactive") as status'))
			->withTrashed()
			->find($state_id);
		$state_travel = DB::table('state_agent_travel_mode')->select('entities.name as travel_mode_name', 'users.name', DB::raw('format(service_charge,2,"en_IN") as service_charge'))->where('state_id', $state_id)->where('entities.company_id', Auth::user()->company_id)
			->leftJoin('entities', 'entities.id', 'state_agent_travel_mode.travel_mode_id')
			->leftJoin('agents', 'agents.id', 'state_agent_travel_mode.agent_id')
			->leftJoin('users', 'users.entity_id', 'agents.id')
			->where('users.user_type_id', 3122)
			->get()->toArray();
		$this->data['travel_mode_name'] = $travel_mode_name = array_column($state_travel, 'travel_mode_name');
		$this->data['agents'] = $agents = array_column($state_travel, 'name');
		$this->data['service_charge'] = $service_charge = array_column($state_travel, 'service_charge');
		$this->data['action'] = 'View';
		if (!$state) {
			$this->data['success'] = false;
			$this->data['errors'] = ['State not found'];
			return response()->json($this->data);
		}
		$this->data['state'] = $state;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//SEARCH AGENT
	// public function searchAgent(Request $r) {
	// 	$key = $r->key;

	// 	$agents_list = Agent::select('users.name as name', 'agents.code', 'agents.id')
	// 		->leftJoin('users', 'users.entity_id', 'agents.id')
	// 		->where('users.user_type_id', 3122)
	// 		->where('agents.company_id', Auth::user()->company_id)

	// 		->where(function ($q) use ($key) {
	// 			$q->where('name', 'like', '%' . $key . '%')
	// 				->orWhere('code', 'like', '%' . $key . '%')
	// 			;
	// 		})
	// 		->get();
	// 	return response()->json($agents_list);
	// }
	public function deleteEYatraState($state_id) {
		$state = Nstate::withTrashed()->where('id', $state_id)->first();
		$state->forceDelete();
		if (!$state) {
			return response()->json(['success' => false, 'errors' => ['State not found']]);
		}
		return response()->json(['success' => true]);
	}
	public function getStateList(Request $request) {
		return NState::getList($request->country_id);
	}

}
