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
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\ReimbursementTranscation;
use Validator;
use Yajra\Datatables\Datatables;

class OutletReimpursementController extends Controller {
	public function listOutletReimpursement(Request $r) {
		$outlets = Outlet::withTrashed()
			->select(
				'outlets.id as outlet_id',
				'outlets.code as outlet_code',
				'outlets.name as outlet_name',
				'outlets.reimbursement_amount as amount',
				'users.name as cashier_name',
				'employees.code as cashier_code'
			)
			->join('employees', 'employees.id', 'outlets.cashier_id')
			->leftjoin('users', function ($join) {
				$join->on('users.entity_id', '=', 'employees.id')
				->where('users.user_type_id',3121);
			})
			->where('outlets.company_id',Auth::user()->company_id)
			->orderBy('outlets.name', 'asc');

		return Datatables::of($outlets)
			->addColumn('action', function ($outlets) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				$outlet_code_name = $outlets->outlet_code . " / " . $outlets->outlet_name;
				$outlet_cashier_name = $outlets->cashier_code;
				$outlet_amount = $outlets->amount;
				return '
				<a href="#!/eyatra/outlet-reimbursement/view/' . $outlets->outlet_id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '">
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#outlet_reimpursement_modal"
                onclick="angular.element(this).scope().outletCashTopup(' . $outlets->outlet_id . ',' . "'" . $outlet_code_name . "'" . ',' . "'" . $outlet_cashier_name . "'" . ',' . "'" . $outlet_amount . "'" . ')" dusk = "delete-btn" title="cash_topup">
                <img src="./public/img/content/yatra/table/ico-rupee.svg"  alt="cash_topup" class="img-responsive" onmouseover=this.src="./public/img/content/yatra/table/ico-rupee-hover.svg" onmouseout=this.src="./public/img/content/yatra/table/ico-rupee.svg" >
                </a>';

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

	public function viewEYatraOutletReimpursement($outlet_id) {
		//dd($outlet_id);
		$reimpurseimpurse_transactions = ReimbursementTranscations::select(
			DB::raw('DATE_FORMAT(transaction_date,"%d/%m/%Y") as date'),
			'amount',
			'balance_amount',
			'configs.name as description',
			'transcation_id',
			DB::raw('IF(petty_cash_id IS NULL,"NULL",petty_cash_id) as petty_cash_id')
		)
			->join('configs', 'configs.id', 'reimbursement_transcations.transcation_id')
			->where('outlet_id', $outlet_id)
			->get();
		//dd($reimpurseimpurse_transactions);
		$reimpurseimpurse_outlet_data = Outlet::select(
			'outlets.name as outlet_name',
			'outlets.code as outlet_code',
			'users.name as cashier_name',
			'employees.code as cashier_code',
			'outlets.reimbursement_amount as reimbursement_amount'
		)
			->join('employees', 'employees.id', 'outlets.cashier_id')
			->join('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('outlets.id', $outlet_id)
			->first();
		// dd($reimpurseimpurse_outlet_data);
		$this->data['reimpurseimpurse_transactions'] = $reimpurseimpurse_transactions;
		$this->data['reimpurseimpurse_outlet_data'] = $reimpurseimpurse_outlet_data;
		$this->data['reimbursement_amount'] = '₹ '.IND_money_format($reimpurseimpurse_outlet_data->reimbursement_amount);

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
	public function cashTopUp(Request $request) {
		//dd($request->all());
		DB::beginTransaction();
		if (isset($request->id)) {
			$outlet = Outlet::Where('id', $request->id)->first();
			$reimbursement_amount = $outlet->reimbursement_amount;
			$outlet->reimbursement_amount = $reimbursement_amount + $request->topup_amount;
			$outlet->save();
			$reimbursement_transaction = new ReimbursementTranscation;
			$reimbursement_transaction->outlet_id = $outlet->id;
			$reimbursement_transaction->transcation_id = 3271;
			$reimbursement_transaction->transaction_date = date('Y-m-d', strtotime($request->transaction_date));
			$reimbursement_transaction->transcation_type = 3271;
			$reimbursement_transaction->amount = $request->topup_amount;
			$reimbursement_transaction->balance_amount = $reimbursement_amount + $request->topup_amount;
			$reimbursement_transaction->save();

		}
		DB::commit();

		if (!$request->id) {
			return response()->json(['success' => false, 'error' => ['Outlet not found']]);
		}
		return response()->json(['success' => true]);
	}
	public function getStateList(Request $request) {
		return NState::getList($request->country_id);
	}

}
