<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Validator;
use Yajra\Datatables\Datatables;

class CityController extends Controller {

	public function listEYatraCity(Request $r) {

		$cities = NCity::withTrashed()->join('nstates', 'nstates.id', 'ncities.state_id')
			->leftjoin('entities', 'entities.id', 'ncities.category_id')
			->select(
				'ncities.id',
				'ncities.name as city_name',
				'nstates.name as state_name',
				'entities.name',
				DB::raw('IF(ncities.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->orderBy('ncities.name', 'asc')
		;
		return Datatables::of($cities)
			->addColumn('action', function ($cities) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/city/edit/' . $cities->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/city/view/' . $cities->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_city"
				onclick="angular.element(this).scope().deleteCityConfirm(' . $cities->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($cities) {
				if ($cities->deleted_at) {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}

			})
			->make(true);
	}

	public function searchCity(Request $request) {

		$key = $request->key;

		$list = NCity::from('ncities')
			->join('nstates as s', 's.id', 'ncities.state_id')
			->select(
				'ncities.id',
				'ncities.name',
				's.name as state_name'
			)
			->where(function ($q) use ($key) {
				$q->where('ncities.name', 'like', '%' . $key . '%')
				;
			})
			->get();
		return response()->json($list);
	}

	public function getCityList(Request $request) {
		return NCity::getList($request->state_id);
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
		$this->data['agents_list'] = $agents_list = Agent::select('name', 'id')->where('company_id', Auth::user()->company_id)->get()->prepend($option);

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

	public function viewEYatraCity($city_id) {

		$city = NCity::withTrashed()->join('nstates', 'nstates.id', 'ncities.state_id')
			->leftjoin('entities', 'entities.id', 'ncities.category_id')
			->select(
				'ncities.id',
				'ncities.name as city_name',
				'nstates.name as state_name',
				'entities.name as category_name',
				DB::raw('IF(ncities.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('ncities.id', $city_id)->first();
		$this->data['city'] = $city;
		$this->data['action'] = 'View';
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraCity($city_id) {
		$city = NCity::withTrashed()->where('id', $city_id)->forceDelete();
		if (!$city) {
			return response()->json(['success' => false, 'errors' => ['City not found']]);
		}
		return response()->json(['success' => true]);
	}
	public function getStateList(Request $request) {
		return NState::getList($request->country_id);
	}

}
