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
use Uitoux\EYatra\Designation;
use Validator;
use Yajra\Datatables\Datatables;

class DesignationController extends Controller {
	public function listEYatraDesignation(Request $r) {
		$designations = Designation::select(
				'designations.id',
				'designations.code',
				'designations.name',
				DB::raw('IF(designations.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->orderBy('designations.name', 'asc');

		return Datatables::of($designations)
			->addColumn('action', function ($state) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/designation/edit/' . $designations->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/designation/view/' . $designations->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_state"
				onclick="angular.element(this).scope().deleteStateConfirm(' . $designations->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($state) {
				if ($state->deleted_at) {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}

			})
			->make(true);
	}

	public function eyatraDesignationFormData($designation_id = NULL) {
		if (!$designation_id) {
			$this->data['action'] = 'Add';
			$designation = new Designation;
			$this->data['status'] = 'Active';

			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$designation = Designation::withTrashed()->find($designation_id);

			if (!$designation) {
				$this->data['success'] = false;
				$this->data['message'] = 'Designation not found';
			}

			if ($designation->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		}
		
		$this->data['success'] = true;
		$this->data['designation'] = $designation;

		return response()->json($this->data);
	}

	public function saveEYatraDesignation(Request $request) {
		//validation
		//dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Designation Code is required',
				'code.unique' => 'Designation Code has already been taken',
				'name.required' => 'Designation Name is required',
				'name.unique' => 'Designation Name has already been taken',

			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'unique:designations,code',
					'required:true',
				],
				
				'name' => [
					'required:true',
					'unique:designations,name'
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$designation = new Designation;
				$designation->created_by = Auth::user()->id;
				$designation->created_at = Carbon::now();
				$designation->updated_at = NULL;

			} else {
				$designation = Designation::withTrashed()->where('id', $request->id)->first();

				$designation->updated_by = Auth::user()->id;
				$designation->updated_at = Carbon::now();
			}
			if ($request->status == 'Active') {
				$designation->deleted_at = NULL;
				$designation->deleted_by = NULL;
			} else {
				$designation->deleted_at = date('Y-m-d H:i:s');
				$designation->deleted_by = Auth::user()->id;

			}

			$designation->fill($request->all());
			$designation->save();

			DB::commit();
			$request->session()->flash('success', 'Designation saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Designation Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Designation Updated Successfully']);
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
		$state_travel = DB::table('state_agent_travel_mode')->select('entities.name as travel_mode_name', 'agents.name', 'service_charge')->where('state_id', $state_id)->where('entities.company_id', Auth::user()->company_id)
			->leftJoin('entities', 'entities.id', 'state_agent_travel_mode.travel_mode_id')
			->leftJoin('agents', 'agents.id', 'state_agent_travel_mode.agent_id')
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
