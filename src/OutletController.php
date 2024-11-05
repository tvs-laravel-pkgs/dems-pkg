<?php
namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Lob;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Sbu;
use Validator;
use App\User;
use Yajra\Datatables\Datatables;

class OutletController extends Controller {
	public function listEYatraOutlet(Request $r) {
		$outlets = Outlet::withTrashed()
			->leftJoin('ey_addresses as a', function ($join) {
				$join->on('a.entity_id', '=', 'outlets.id')
					->where('a.address_of_id', 3160);
			})
			->leftJoin('ncities as city', 'city.id', 'a.city_id')
			->leftJoin('nstates as s', 's.id', 'city.state_id')
			->leftjoin('regions as r', 'r.state_id', 's.id')
			->leftJoin('countries as c', 'c.id', 's.country_id')
			->leftJoin('employees', 'employees.id', 'outlets.cashier_id')
			// ->leftJoin('users', function ($join) {
			// 	$join->on('users.entity_id', 'employees.id')
			// 		->where('users.user_type_id', 3121);
			// })
			->leftJoin('businesses', 'businesses.id', 'outlets.business_id')
			->select(
				'outlets.id',
				'outlets.code',
				'outlets.name',
				'outlets.cashier_id',
				DB::raw('IF(city.name IS NULL,"---",city.name) as city_name'),
				// 'city.name as city_name',
				DB::raw('IF(s.name IS NULL,"---",s.name) as state_name'),
				// 's.name as state_name',
				DB::raw('IF(r.name IS NULL,"---",r.name) as region_name'),
				DB::raw('IF(c.name IS NULL,"---",c.name) as country_name'),
				// 'c.name as country_name',
				DB::raw('IF(outlets.deleted_at IS NULL,"Active","Inactive") as status'),
				'employees.code as emp_code',
				// 'users.name as emp_name',
				'businesses.name as business_name'
			)
			->where('outlets.company_id', Auth::user()->company_id)
		// ->where('a.address_of_id', 3160)
			->where(function ($query) use ($r) {
				if ($r->get('cashier_id')) {
					$query->where("outlets.cashier_id", $r->get('cashier_id'))->orWhere(DB::raw("-1"), $r->get('cashier_id'));
				}
			})
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
			->orderBy('outlets.name')
			->groupBy('outlets.id');

		return Datatables::of($outlets)
			->addColumn('action', function ($outlet) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';
				$edit_class = "visibility:hidden";
				if (Entrust::can('eyatra-outlet-edit')) {
					$edit_class = "";
				}
				$delete_class = "visibility:hidden";
				if (Entrust::can('eyatra-outlet-delete')) {
					$delete_class = "";
				}

				$action .= '<a style="' . $edit_class . '" href="#!/outlet/edit/' . $outlet->id . '"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a> ';
				$action .= '<a href="#!/outlet/view/' . $outlet->id . '"><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a> ';
				$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_outlet" onclick="angular.element(this).scope().deleteOutletConfirm(' . $outlet->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" ></a>';

				return $action;
			})
			->addColumn('status', function ($outlet) {
				if ($outlet->status == 'Inactive') {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}
			})
			->addColumn('cashier_name', function ($outlet) {
     			$outlet_cashier = User::withTrashed()
     				->select('name')
     				->where('entity_id', $outlet->cashier_id)
     				->where('user_type_id', 3121) //Employee
     				->first();
     			return $outlet_cashier ? $outlet_cashier->name : "---";
     		})
			->make(true);
	}

	public function eyatraOutletFormData($outlet_id = NULL) {
		if (!$outlet_id) {
			$this->data['action'] = 'Add';
			$outlet = new Outlet;
			$address = new Address;
			$this->data['status'] = 'Active';
			$this->data['amount_eligiblity'] = 'No';
			$this->data['amount_approver'] = 'Cashier';
			$this->data['success'] = true;
			$this->data['lob_outlet'] = $lobs = Lob::
				where('company_id', Auth::user()->company_id)
				->get()
			;
			$this->data['sbu_outlet'] = [];

		} else {
			$this->data['action'] = 'Edit';
			$outlet = Outlet::with('sbu', 'address', 'address.city', 'address.city.state')->withTrashed()->find($outlet_id);
			//dd($outlet);
			$outlet->cashier = $outlet->employee ? $outlet->employee->user : '';
			$outlet->nodel = $outlet->employeeNodel ? $outlet->employeeNodel->user : '';
			// $outlet->cashier = Employee::select('code', 'id')->where('id', $outlet->employee->id)->first();
			// dd($outlet->cashier);
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
				$this->data['amount_eligiblity'] = 'Yes';
			} else {
				$this->data['amount_eligiblity'] = 'No';
			}
			if ($outlet->claim_req_approver == 1) {
				$this->data['amount_approver'] = 'Cashier';
			} else {
				$this->data['amount_approver'] = 'Financier';
			}
			//dd($outlet->outletBudgets);
			//$sbu_ids=$outlet->outletBudgets->groupBy('pivot_sbu_id')->pluck('pivot_sbu_id');
			//dd($sbu_ids);
			$this->data['lob_outlet'] = $lobs = Lob::
				where('company_id', Auth::user()->company_id)
				->get()
			;
			$lob_ids = Lob::
				where('company_id', Auth::user()->company_id)
				->pluck('id')
			;
			$sbus = Sbu::whereIn('lob_id', $lob_ids)
				->orderBy('id')
				->get();
			//Getting checked lob ids and unique values from that
			$checked_lob_ids = [];
			$ckecked_lob_ids_unique = [];
			foreach ($outlet->outletBudgets as $key => $outlet_budget) {
				$checked_lob_ids[] = $outlet_budget->lob_id;
			}
			$ckecked_lob_ids_unique = array_unique($checked_lob_ids);

			$this->data['sbu_outlet'] = Sbu::select('name', 'id')
				->whereIn('lob_id', $ckecked_lob_ids_unique)
				->orderBy('id')
				->get()
			;
			//Getting checked sbu ids and unique values from that
			$checked_sbu_ids = [];
			$ckecked_sbu_ids_unique = [];

			foreach ($outlet->outletBudgets as $key => $outlet_sbu) {
				$this->data['sbu_outlet'][$key]->checked = true;
				$this->data['sbu_outlet'][$key]->sbu_id = $outlet_sbu->pivot->sbu_id;
				$this->data['sbu_outlet'][$key]->outstation_budget_amount = $outlet_sbu->pivot->outstation_budget_amount;
				$this->data['sbu_outlet'][$key]->local_budget_amount = $outlet_sbu->pivot->local_budget_amount;
			}

			foreach ($outlet->outletBudgets as $key => $outlet_budget) {
				$checked_sbu_ids[] = $outlet_budget->id;
			}
			$ckecked_sbu_ids_unique = array_unique($checked_sbu_ids);
			foreach ($lobs as $key => $lob) {
				if (in_array($lob->id, $ckecked_lob_ids_unique)) {
					$this->data['lob_outlet'][$key]->checked = true;
				} else {
					$this->data['lob_outlet'][$key]->checked = false;
				}
			}
		}
		// dd(Auth::user()->company_id);
		$lob_list = collect(Lob::select('name', 'id')->where('company_id', Auth::user()->company_id)->get());
		// $cashier_list = Employee::select('name', 'code', 'id')->get();
		$sbu_list = [];
		$this->data['extras'] = [
			'country_list' => NCountry::getList(),
			'state_list' => $this->data['action'] == 'Add' ? [] : NState::getList($outlet->address ?$outlet->address->city->state->country_id : ''),
			'city_list' => $this->data['action'] == 'Add' ? [] : NCity::getList($outlet->address ? $outlet->address->state_id : ''),
			'lob_list' => $lob_list,
			'sbu_list' => $sbu_list,
			'cashier_list' => Employee::getList(),
			'nodel_list' => Employee::getList(),
			'business_list' => collect(Business::select('name', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Business']),
			// 'city_list' => NCity::getList(),
		];
		//$this->data['sbu_outlet'] = [];
		// foreach ($lob_outlet->sbus as $lob_sbu) {
		// $this->data['lob_outlet'][$lob_sbu->id]->checked = true;
		// }
		$this->data['outlet'] = $outlet;
		$this->data['address'] = $outlet->address;
		$this->data['success'] = true;
		//dd($this->data);
		//dd($outlet);
		return response()->json($this->data);
	}
	public function eyatraOutletFilterData() {
		$this->data['region_list'] = Region::getList();
		$this->data['city_list'] = NCity::getList();
		$this->data['country_list'] = $country = NCountry::getList();
		$option = new NState;
		$option->name = 'Select State';
		$option->id = null;
		$this->data['state_list'] = $state_list = collect(NState::select('name', 'id')
				->get())->prepend($option);
		$option = new Employee;
		$option->name = 'Select Cashier';
		$option->id = null;
		// $this->data['cashier_list'] = $cashier_list = collect(Employee::select('name', 'id')
		// $this->data['cashier_list'] = $cashier_list = collect(Employee::join('users', 'users.entity_id', 'employees.id')->join('role_user', 'role_user.user_id', 'users.id')->where('role_user.role_id', 504)->where('users.company_id', Auth::user()->company_id)->where('users.user_type_id', 3121)->select(
		// 	DB::raw('concat(employees.code," - ",users.name) as name'), 'employees.id')
		// 		->get())->prepend($option);
		$this->data['cashier_list'] = $cashier_list = collect(Employee::join('users', 'users.entity_id', 'employees.id')->join('outlets', 'outlets.cashier_id', 'employees.id')->where('users.company_id', Auth::user()->company_id)->where('users.user_type_id', 3121)->select(
			DB::raw('concat(employees.code," - ",users.name) as name'), 'employees.id')
				->groupBy('employees.id')
				->get())->prepend($option);
		$this->data['nodel_list'] = $nodel_list = collect(Employee::join('users', 'users.entity_id', 'employees.id')->join('outlets', 'outlets.cashier_id', 'employees.id')->where('users.company_id', Auth::user()->company_id)->where('users.user_type_id', 3121)->select(
			DB::raw('concat(employees.code," - ",users.name) as name'), 'employees.id')
				->groupBy('employees.id')
				->get())->prepend($option);
		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}

	public function stateFilterList($id = NULL) {

		$option = new NState;
		$option->name = 'Select State';
		$option->id = null;
		$state_list = NState::select('name', 'id')->where('country_id', $id)->get();
		$this->data['state_list'] = $state_list->prepend($option);
		return response()->json($this->data);

	}

	public function cityFilterList($id = NULL) {

		$option = new NCity;
		$option->name = 'Select City';
		$option->id = null;
		$city_list = NCity::select('name', 'id')->where('state_id', $id)->get();
		$this->data['city_list'] = $city_list->prepend($option);
		return response()->json($this->data);

	}

//SEARCH CASHIER
	public function searchCashier(Request $r) {
		$key = $r->key;
		$cashier_list = Employee::select(

			'users.name',
			'employees.code',
			'employees.id as entity_id',
			'businesses.name as business_name'
		)
			->join('users', 'users.entity_id', 'employees.id')
			->leftjoin('businesses', 'businesses.id', 'employees.business_id')
			->where('users.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)
			->where(function ($q) use ($key) {
				$q->where('employees.code', 'like', '%' . $key . '%')
					->orWhere('users.name', 'like', '%' . $key . '%')
				;
			})

			->get();
		return response()->json($cashier_list);
	}
	//SEARCH NODEL
	public function searchNodel(Request $r) {
		$key = $r->key;
		$nodel_list = Employee::select(

			'users.name',
			'employees.code',
			'employees.id as entity_id',
			'businesses.name as business_name'
		)
			->join('users', 'users.entity_id', 'employees.id')
			->leftjoin('businesses', 'businesses.id', 'employees.business_id')
			->where('users.user_type_id', 3121)
			->where('employees.company_id', Auth::user()->company_id)
			->where(function ($q) use ($key) {
				$q->where('employees.code', 'like', '%' . $key . '%')
					->orWhere('users.name', 'like', '%' . $key . '%')
				;
			})

			->get();
		return response()->json($nodel_list);
	}
	public function saveEYatraOutlet(Request $request) {
		// dd($request->all());
		//validation
		try {
			$error_messages = [
				'code.required' => 'Outlet Code is Required',
				'outlet_name.required' => 'Outlet Name is Required',
				'business_id.required' => 'Business is Required',
				'line_1.required' => 'Address Line1 is Required',
				'city_id.required' => 'City is Required',
				'pincode.required' => 'Pincode is Required',
				'code.unique' => "Outlet Code is already taken",
				'outlet_name.unique' => "Outlet Name is already taken",
				'cashier_id.required' => "Cashier Name is Required",
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'outlet_name' => 'required',
				'business_id' => 'required',
				'line_1' => 'required',
				'cashier_id' => 'required',
				'nodel_id'=>'required',
				// 'amount_eligible' => 'required',
				// 'country_id' => 'required',
				// 'state_id' => 'required',
				'city_id' => 'required',
				'pincode' => 'required',
				// 'code' => 'required|unique:outlets,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				'code' => 'required|unique:outlets,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',business_id,' . $request->business_id,
				// 'outlet_name' => 'required|unique:outlets,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				'outlet_name' => 'required|unique:outlets,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',business_id,' . $request->business_id,
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
				$address = Address::where('entity_id', $request->id)
					->where('address_of_id',3160)
					->first();
				if(!$address){
					$address = new Address;
				}
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
			if ($request->amount_eligiblity == 'No') {
				$outlet->amount_eligible = 0;
				$outlet->amount_limit = NULL;
			} else {
				$outlet->amount_eligible = 1;
			}
			if ($request->amount_approver == 'Cashier') {
				$outlet->claim_req_approver = 1;
			} else {
				$outlet->claim_req_approver = 0;
			}
			$outlet->name = $request->outlet_name;
			$outlet->minimum_threshold_balance = $request->minimum_threshold_balance;
			$outlet->company_id = Auth::user()->company_id;
			$outlet->fill($request->all());
			$outlet->save();
			//dd('s');
			$activity['entity_id'] = $outlet->id;
			$activity['entity_type'] = "Outlet";
			$activity['details'] = empty($request->id) ? "Outlet is  Added" : "Outlet is  updated";
			$activity['activity'] = empty($request->id) ? "Add" : "Edit";
			$activity_log = ActivityLog::saveLog($activity);
			//SAVING ADDRESS
			$address->address_of_id = 3160;
			$address->entity_id = $outlet->id;
			$address->name = 'Primary';
			$address->fill($request->all());
			$address->save();

			//SAVING OUTLET BUDGET
			// $sbu_ids = array_column($request->sbus, 'sbu_id');

			if ($request->sbus) {
				$outlet->outletBudgets()->sync([]);
				foreach ($request->sbus as $sbu) {
					if (!isset($sbu['sbu_id'])) {
						continue;
					}
					$outlet->outletBudgets()->attach(
						$sbu['sbu_id'],
						['outstation_budget_amount' => isset($sbu['outstation_budget_amount']) ? $sbu['outstation_budget_amount'] : 0, 'local_budget_amount' => isset($sbu['local_budget_amount']) ? $sbu['local_budget_amount'] : 0]
					);
				}
			}

			DB::commit();
			$request->session()->flash('success', 'Outlet saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Outlet Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Outlet Updated Successfully']);
			}
			return response()->json(['success' => true]);
		} catch (\Exception $e) {
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
            'employee.user',
            'employeeNodel',
            'employeeNodel.user',
            'businessData'
		])->select('*', DB::raw('IF(outlets.amount_eligible = 1,"Yes","No") as amount_eligible'), DB::raw('format(amount_limit,2,"en_IN") as amount_limit'), DB::raw('IF(outlets.deleted_at IS NULL,"Active","Inactive") as status'))
			->withTrashed()
			->find($outlet_id);
		$outlet_budget = DB::table('outlet_budget')->select('lobs.name as lob_name', 'sbus.name as sbu_name', DB::raw('format(outstation_budget_amount,2,"en_IN") as outstation_budget_amount'),DB::raw('format(local_budget_amount,2,"en_IN") as local_budget_amount'))->where('outlet_budget.outlet_id', $outlet_id)
			->leftJoin('sbus', 'sbus.id', 'outlet_budget.sbu_id')
			->leftJoin('lobs', 'lobs.id', 'sbus.lob_id')
			->get()->toArray();
		$this->data['lob_name'] = $lob_name = array_column($outlet_budget, 'lob_name');
		$this->data['sbu_name'] = $sbu_name = array_column($outlet_budget, 'sbu_name');
		$this->data['outstation_budget_amount'] = $amount = array_column($outlet_budget, 'outstation_budget_amount');
		$this->data['local_budget_amount'] = $amount = array_column($outlet_budget, 'local_budget_amount');
		if (!$outlet) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Outlet not found'];
			return response()->json($this->data);
		}
		// dd($outlet);
		$this->data['action'] = 'View';
		$this->data['outlet'] = $outlet;
		$this->data['success'] = true;
		//dd($this->data);

		return response()->json($this->data);
	}
	public function deleteEYatraOutlet($outlet_id) {
		$outlet = Outlet::where('id', $outlet_id)->first();
		$activity['entity_id'] = $outlet->id;
		$activity['entity_type'] = "outlet";
		$activity['details'] = "Outlet is deleted";
		$activity['activity'] = "Delete";
		$activity_log = ActivityLog::saveLog($activity);
		$outlet->forceDelete();
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