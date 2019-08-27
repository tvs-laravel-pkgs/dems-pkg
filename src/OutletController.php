<?php
namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Lob;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Sbu;
use Validator;
use Yajra\Datatables\Datatables;

class OutletController extends Controller {
	public function listEYatraOutlet(Request $r) {
		$outlets = Outlet::withTrashed()
			->join('ey_addresses as a', 'a.entity_id', 'outlets.id')
			->join('ncities as city', 'city.id', 'a.city_id')
			->join('nstates as s', 's.id', 'city.state_id')
			->leftjoin('regions as r', 'r.state_id', 's.id')
			->join('country as c', 'c.id', 's.country_id')
			->select(
				'outlets.id',
				'outlets.code',
				'outlets.name',
				'city.name as city_name',
				's.name as state_name',
				DB::raw('IF(r.name IS NULL,"---",r.name) as region_name'),
				'c.name as country_name',
				DB::raw('IF(outlets.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('outlets.company_id', Auth::user()->company_id)
			->where('a.address_of_id', 3160)
			->where(function ($query) use ($r) {
				if ($r->get('region_id')) {
					$query->where("r.id", $r->get('region_id'))->orWhere(DB::raw("-1"), $r->get('region_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('city_id')) {
					$query->where("city.id", $r->get('city_id'))->orWhere(DB::raw("-1"), $r->get('city_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('state_id')) {
					$query->where("s.id", $r->get('state_id'))->orWhere(DB::raw("-1"), $r->get('state_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('country_id')) {
					$query->where("c.id", $r->get('country_id'))->orWhere(DB::raw("-1"), $r->get('country_id'));
				}
			})
			->groupBy('outlets.id');
		// if (!Entrust::can('view-all-trips')) {
		// $trips->where('trips.employee_id', Auth::user()->entity_id);
		// }
		return Datatables::of($outlets)
			->addColumn('action', function ($outlet) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
        <a href="#!/eyatra/outlet/edit/' . $outlet->id . '">
          <img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
        </a>
        <a href="#!/eyatra/outlet/view/' . $outlet->id . '">
          <img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
        </a>
        <a href="javascript:;" data-toggle="modal" data-target="#delete_outlet"
        onclick="angular.element(this).scope().deleteOutletConfirm(' . $outlet->id . ')" dusk = "delete-btn" title="Delete">
        <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
        </a>';
			})
			->addColumn('status', function ($outlet) {
				if ($outlet->status == 'Inactive') {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}
			})
			->make(true);
	}

	public function eyatraOutletFormData($outlet_id = NULL) {
		if (!$outlet_id) {
			$this->data['action'] = 'Add';
			$outlet = new Outlet;
			$address = new Address;
			$this->data['status'] = 'Active';
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$outlet = Outlet::with('sbu', 'address', 'address.city', 'address.city.state')->withTrashed()->find($outlet_id);
			$outlet->cashier = Employee::select('code', 'id')->where('id', $outlet->employee->id)->first();
			// $this->data['cashier'] = Employee::select('code', 'id')->where('id', $outlet->employee->id)->first();
			// dd($outlet->employee->id);
			if (!$outlet) {
				$this->data['success'] = false;
				$this->data['message'] = 'Outlet not found';
			}
			if ($outlet->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
			if ($outlet->amount_eligible == 1) {
				$outlet->amount_eligible = 1;
			} else {
				$outlet->amount_eligible = 0;
			}
			// dd($outlet->outletBudgets);
			// $this->data['sbu_outlet'] = Sbu::select(
			// 'name',
			// 'id',
			// )
			// // ->whereIn('lob_id', $outlet->Sbu)
			// ->get()
			// ;
			// foreach ($lob_outlet->sbus as $lob_sbu) {
			// $this->data['lob_outlet'][$lob_sbu->id]->checked = true;
			// }
			// foreach ($outlet->outletBudgets as $outlet_sbu) {
			// $this->data['sbu_outlet'][$outlet_sbu->id]->checked = true;
			// $this->data['sbu_outlet'][$outlet_sbu->id]->sbu_id = $outlet_sbu->pivot->sbu_id;
			// $this->data['sbu_outlet'][$outlet_sbu->id]->amount = $outlet_sbu->pivot->amount;
			// }
		}
		// dd(Auth::user()->company_id);
		$lob_list = collect(Lob::select('name', 'id')->where('company_id', Auth::user()->company_id)->get());
		// $cashier_list = Employee::select('name', 'code', 'id')->get();
		$sbu_list = [];
		$this->data['extras'] = [
			'country_list' => NCountry::getList(),
			'state_list' => $this->data['action'] == 'Add' ? [] : NState::getList($outlet->address->city->state->country_id),
			'city_list' => $this->data['action'] == 'Add' ? [] : NCity::getList($outlet->address->state_id),
			'lob_list' => $lob_list,
			'sbu_list' => $sbu_list,
			'cashier_list' => Employee::getList(),
			// 'city_list' => NCity::getList(),
		];
		// $this->data['lob_outlet'] = $lob_outlet = Lob::select('name', 'id')->get();
		$this->data['sbu_outlet'] = [];
		// foreach ($lob_outlet->sbus as $lob_sbu) {
		// $this->data['lob_outlet'][$lob_sbu->id]->checked = true;
		// }
		$this->data['outlet'] = $outlet;
		$this->data['address'] = $outlet->address;
		$this->data['success'] = true;
		return response()->json($this->data);
	}
	public function eyatraOutletFilterData() {
		$this->data['region_list'] = Region::getList();
		$this->data['city_list'] = NCity::getList();
		$this->data['state_list'] = NState::getList();
		$this->data['country_list'] = NCountry::getList();
		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

//SEARCH CASHIER
	public function searchCashier(Request $r) {
		$key = $r->key;
		$cashier_list = Employee::select(
			'name',
			'code',
			'id'
		)
			->where(function ($q) use ($key) {
				$q->where('name', 'like', '%' . $key . '%')
					->orWhere('code', 'like', '%' . $key . '%')
				;
			})
			->get();
		return response()->json($cashier_list);
	}
	public function saveEYatraOutlet(Request $request) {
		//validation
		try {
			$error_messages = [
				'code.required' => 'Outlet Code is Required',
				'outlet_name.required' => 'Outlet Name is Required',
				'line_1.required' => 'Address Line1 is Required',
				// 'country_id.required' => 'Country is Required',
				// 'state_id.required' => 'State is Required',
				'city_id.required' => 'City is Required',
				'pincode.required' => 'Pincode is Required',
				'code.unique' => "Outlet Code is already taken",
				'outlet_name.unique' => "Outlet Name is already taken",
				'cashier_id.required' => "Cashier Name is Required",
				// 'amount_eligible.required' => "Amount Eligible is Required",
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'outlet_name' => 'required',
				'line_1' => 'required',
				'cashier_id' => 'required',
				// 'amount_eligible' => 'required',
				// 'country_id' => 'required',
				// 'state_id' => 'required',
				'city_id' => 'required',
				'pincode' => 'required',
				'code' => 'required|unique:outlets,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				'outlet_name' => 'required|unique:outlets,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			// dd($request->all());
			DB::beginTransaction();
			if (!$request->id) {
				$outlet = new Outlet;
				$address = new Address;
				$outlet->created_by = Auth::user()->id;
				$outlet->created_at = Carbon::now();
				$outlet->updated_at = NULL;
			} else {
				$outlet = Outlet::withTrashed()->find($request->id);
				$address = Address::where('entity_id', $request->id)->first();
				$outlet->updated_by = Auth::user()->id;
				$outlet->updated_at = Carbon::now();
				$outlet->outletBudgets()->sync([]);
			}
			if ($request->status == 'Active') {
				$outlet->deleted_at = NULL;
				$outlet->deleted_by = NULL;
			} else {
				$outlet->deleted_at = date('Y-m-d H:i:s');
				$outlet->deleted_by = Auth::user()->id;
			}
			if ($request->amount_eligible == 1) {
				$outlet->amount_eligible = 1;
				$outlet->amount_limit = $request->amount_limit;
			} else {
				$outlet->amount_eligible = 0;
				$outlet->amount_limit = NULL;
			}
			$outlet->name = $request->outlet_name;
			$outlet->company_id = Auth::user()->company_id;
			$outlet->fill($request->all());
			$outlet->save();
			//SAVING ADDRESS
			$address->address_of_id = 3160;
			$address->entity_id = $outlet->id;
			$address->name = 'Primary';
			$address->fill($request->all());
			$address->save();
			// dd($address);
			//SAVING OUTLET BUDGET
			$sbu_ids = array_column($request->sbus, 'sbu_id');
			// dd($sbu_ids);
			if (count($request->sbus) > 0) {
				foreach ($request->sbus as $sbu) {
					if (!isset($sbu['sbu_id'])) {
						continue;
					}
					$outlet->outletBudgets()->attach($sbu['sbu_id'], [
						'amount' => isset($sbu['amount']) ? $sbu['amount'] : NULL,
					]);
				}
			}
			DB::commit();
			$request->session()->flash('success', 'outlet saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Outlet Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Outlet Updated Successfully']);
			}
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function viewEYatraOutlet($outlet_id) {
		$outlet = Outlet::with([
			'address',
			'address.city',
			'address.city.state',
			'address.city.state.country',
			'Sbu',
			'Sbu.lob',
			'employee',
		])->select('*', DB::raw('IF(outlets.amount_eligible = 1,"Yes","No") as amount_eligible'), DB::raw('IF(outlets.deleted_at IS NULL,"Active","Inactive") as status'))
			->withTrashed()
			->find($outlet_id);
		$outlet_budget = DB::table('outlet_budget')->select('lobs.name as lob_name', 'sbus.name as sbu_name', 'amount')->where('outlet_id', $outlet_id)

			->leftJoin('sbus', 'sbus.id', 'outlet_budget.sbu_id')
			->leftJoin('lobs', 'lobs.id', 'sbus.lob_id')
			->get()->toArray();
		$this->data['lob_name'] = $lob_name = array_column($outlet_budget, 'lob_name');
		$this->data['sbu_name'] = $sbu_name = array_column($outlet_budget, 'sbu_name');
		$this->data['amount'] = $amount = array_column($outlet_budget, 'amount');
		if (!$outlet) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Outlet not found'];
			return response()->json($this->data);
		}
		// dd($outlet);
		$this->data['action'] = 'View';
		$this->data['outlet'] = $outlet;
		$this->data['success'] = true;
		return response()->json($this->data);
	}
	public function deleteEYatraOutlet($outlet_id) {
		$outlet = Outlet::where('id', $outlet_id)->forceDelete();
		if (!$outlet) {
			return response()->json(['success' => false, 'errors' => ['Outlet not found']]);
		}
		return response()->json(['success' => true]);
	}
	public function getSbuByLob(Request $request) {
		if (!empty($request->lob_id)) {
			$sbu_list = collect(Sbu::where('lob_id', $request->lob_id)->select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Sbu']);
		} else {
			$sbu_list = [];
		}
		return response()->json(['sbu_list' => $sbu_list]);
	}
	public function getOutletSbuByLob($id) {
		if (!empty($id)) {
			$sbu_outlet = Sbu::select('name', 'id', 'lob_id')->where('lob_id', $id)->get();
		} else {
			$sbu_outlet = NULL;
		}
		return response()->json(['sbu_outlet' => $sbu_outlet]);
	}
}