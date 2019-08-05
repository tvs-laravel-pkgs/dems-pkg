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
				<a href="#!/eyatra/trip/edit/' . $agent->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/trip/view/' . $agent->id . '">
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
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$agent = Agent::find($agent_id);
			if (!$agent) {
				$this->data['success'] = false;
				$this->data['message'] = 'Agent not found';
			}
		}
		$this->data['extras'] = [
			'travel_mode_list' => Entity::travelModeList(),
			'country_list' => NCountry::getList(),
			'state_list' => $this->data['action'] = 'New' ? [] : NState::getList($agent->address->country_id),
			'city_list' => $this->data['action'] = 'New' ? [] : NCity::getList($agent->address->state_id),
		];
		$this->data['agent'] = $agent;

		return response()->json($this->data);
	}

	public function saveEYatraAgent(Request $request) {
		//validation
		try {
			$validator = Validator::make($request->all(), [
				'purpose_id' => [
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
					if ($visit_data['booking_method'] == 'Agent') {
						// $agent = Agent::where('company_id', Auth::user()->company_id)
						// 	->join('agent_travel_mode as atm', 'atm.agent_id', 'agents.id')
						// 	->where('atm.state_id', Auth::user()->eyatraEmployee->outlet->address->state_id)
						// 	->where('atm.travel_mode_id', $visit_data['travel_mode_id'])
						// 	->first();
						// if ($agent) {
						// 	$visit->agent_id = $agent->id;
						// } else {
						// 	return response()->json(['success' => false, 'errors' => ['No agent found for visit']]);
						// }
					}
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

	public function viewEYatraAgent($agent_id) {

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

	public function deleteEYatraAgent($agent_id) {
		$trip = Trip::where('id', $trip_id)->delete();
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		return response()->json(['success' => true]);
	}

}
