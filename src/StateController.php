<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Visit;
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
			}
		}
		$this->data['country_list'] = $country_list = NCountry::select('name', 'id')->get();
		$this->data['travel_modes'] = $travel_modes = Entity::select('name')->where('entity_type_id', 502)->where('company_id', Auth::user()->company_id)->get();
		// foreach ($travel_modes as $travel_mode) {
		// 		$this->data['role_list'][$user_role->id]->checked = true;
		// 	}
		$this->data['agents_list'] = $agents_list = Agent::select('name', 'id')->where('company_id', Auth::user()->company_id)->get();

		// DB::table('state_agent_travel_mode')->select();
		$this->data['state'] = $state;

		return response()->json($this->data);
	}

	public function saveEYatraState(Request $request) {
		//validation
		try {
			$validator = Validator::make($request->all(), [
				'code' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$trip = new Trip;
				$trip->created_by = Auth::user()->id;
				$trip->created_at = Carbon::now();
				$trip->updated_at = NULL;

			} else {
				$trip = Trip::find($request->id);

				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();

				$trip->visits()->sync([]);

			}
			$trip->fill($request->all());
			$trip->number = 'TRP' . rand();
			$trip->employee_id = Auth::user()->entity->id;
			$trip->status_id = 3020; //NEW
			$trip->save();

			$trip->number = 'TRP' . $trip->id;
			$trip->save();

			//SAVING VISITS
			if ($request->visits) {
				foreach ($request->visits as $visit_data) {
					$visit = new Visit;
					$visit->fill($visit_data);
					$visit->trip_id = $trip->id;
					$visit->booking_method_id = $visit_data['booking_method'] == 'Self' ? 3040 : 3042;
					$visit->booking_status_id = 3060; //PENDING
					$visit->status_id = 3020; //NEW
					$visit->manager_verification_status_id = 3080; //NEW
					$visit->save();
				}
			}

			DB::commit();
			$request->session()->flash('success', 'Trip saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraState($agent_id) {

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
