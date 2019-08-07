<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
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
	public function listEYatraState(Request $r) {
		$states = NState::from('nstates')
			->join('countries as c', 'c.id', 'nstates.country_id')
			->select(
				'nstates.id',
				'nstates.code',
				'nstates.name',
				'c.name as country',
				DB::raw('IF(nstates.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->orderBy('nstates.name', 'asc');

		return Datatables::of($states)
			->addColumn('action', function ($state) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/state/edit/' . $state->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/state/view/' . $state->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_emp"
				onclick="angular.element(this).scope().deleteState(' . $state->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})
			->make(true);
	}

	public function eyatraStateFormData($state_id = NULL) {
		if (!$state_id) {
			$this->data['action'] = 'Add';
			$state = new NState;
			$this->data['status'] = 'Active';
			//$visit = new Visit;
			// $visit->booking_method = 'Self';
			// $trip->visits = [$visit];
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$state = NState::find($state_id);
			if ($state->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}

			if (!$state) {
				$this->data['success'] = false;
				$this->data['message'] = 'State not found';
			} else {
				$this->data['success'] = true;

			}
		}
		$this->data['country_list'] = $country_list = NCountry::select('name', 'id')->get();
		$this->data['travel_modes'] = $travel_modes = Entity::select('name', 'id')->where('entity_type_id', 502)->where('company_id', Auth::user()->company_id)->get();
		foreach ($travel_modes as $travel_mode) {
			$this->data['agents_list'] = $agents_list[] = Agent::select('name', 'id')->where('company_id', Auth::user()->company_id)->get();
		}

		// DB::table('state_agent_travel_mode')->select();
		$this->data['state'] = $state;

		return response()->json($this->data);
	}

	public function saveEYatraState(Request $request) {
		//validation
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Short Name is required',
				'code.unique' => 'Short Name has already been taken',
				'name.required' => 'Name is required',
				'name.unique' => 'Name has already been taken',

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
				$state = NState::find($request->id);

				$state->updated_by = Auth::user()->id;
				$state->updated_at = Carbon::now();

				//$trip->visits()->sync([]);
				//dd($request->travel_modes);
			}
			if ($request->status == 'Inactive') {
				$state->deleted_at = date('Y-m-d H:i:s');
			} else {
				$state->deleted_at = NULL;
			}
			$state->fill($request->all());
			$state->save();
			//SAVING state_agent_travel_mode
			// $travel_modes = [];
			// $agents = [];

			foreach (NState::get() as $state) {
				$state->travelModes()->sync([]);
				$state->agents()->sync([]);
				foreach (Entity::travelModeList() as $travel_mode) {
					$agent = Agent::whereHas('travelModes', function ($query) use ($travel_mode) {
						$query->where('id', $travel_mode->id);
					})->inRandomOrder()->get();
					$travel_modes[$travel_mode->id] = [
						'state_id' => $request->id,
						'agent_id' => $request->agent_id,
						'service_charge' => $request->service_charge,
					];

				}
				$state->travelModes()->sync($travel_modes);
				$state->agents()->sync($agents);

			}
			//dd($request->travel_mode);
			DB::commit();
			$request->session()->flash('success', 'State saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraState($state_id) {

		$state = NState::with([

			'country',
		])
			->find($state_id);
		if (!$state) {
			$this->data['success'] = false;
			$this->data['errors'] = ['State not found'];
			return response()->json($this->data);
		}

		$this->data['state'] = $state;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraState($agent_id) {
		$trip = Trip::where('id', $trip_id)->delete();
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		return response()->json(['success' => true]);
	}

	public function getStateList(Request $request) {
		return NState::getList($request->country_id);
	}

}
