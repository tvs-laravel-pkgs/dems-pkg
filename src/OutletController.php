<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Lob;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Sbu;
use Validator;
use Yajra\Datatables\Datatables;

class OutletController extends Controller {
	public function listEYatraOutlet(Request $r) {
		$outlets = Outlet::withTrashed()->from('outlets')
			->join('ey_addresses as a', 'a.entity_id', 'outlets.id')
			->join('ncities as city', 'city.id', 'a.city_id')
			->join('nstates as s', 's.id', 'city.state_id')
			->join('countries as c', 'c.id', 's.country_id')
			->select(
				'outlets.id',
				'outlets.code',
				'outlets.name',
				'city.name as city_name',
				's.name as state_name',
				'c.name as country_name',
				DB::raw('IF(outlets.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('outlets.company_id', Auth::user()->company_id)
			->where('a.address_of_id', 3160)
			->groupBy('outlets.id');

		// if (!Entrust::can('view-all-trips')) {
		// 	$trips->where('trips.employee_id', Auth::user()->entity_id);
		// }
		return Datatables::of($outlets)
			->addColumn('action', function ($outlet) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
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
		} else {
			$this->data['action'] = 'Edit';
			$outlet = Outlet::with('Sbu', 'address', 'address.city', 'address.city.state')->withTrashed()->find($outlet_id);
			// $outlet->address;
			// dd($outlet->address);

			if (!$outlet) {
				$this->data['success'] = false;
				$this->data['message'] = 'Outlet not found';
			}
			if ($outlet->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		}

		$lob_list = collect(Lob::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Lob']);
		$sbu_list = [];
		$this->data['extras'] = [
			'country_list' => NCountry::getList(),
			'state_list' => $this->data['action'] == 'Add' ? [] : NState::getList($outlet->address->city->state->country_id),
			'city_list' => $this->data['action'] == 'Add' ? [] : NCity::getList($outlet->address->state_id),
			'lob_list' => $lob_list,
			'sbu_list' => $sbu_list,
			// 'city_list' => NCity::getList(),
		];

		$this->data['outlet'] = $outlet;
		$this->data['address'] = $outlet->address;
		$this->data['success'] = true;

		return response()->json($this->data);
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
				'cashier_name.required' => "Cashier Name is Required",
				'amount_eligible.required' => "Amount Eligible is Required",
			];

			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'outlet_name' => 'required',
				'line_1' => 'required',
				'cashier_name' => 'required',
				'amount_eligible' => 'required',
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

			}
			if ($request->status == 'Active') {
				$outlet->deleted_at = NULL;
				$outlet->deleted_by = NULL;
			} else {
				$outlet->deleted_at = date('Y-m-d H:i:s');
				$outlet->deleted_by = Auth::user()->id;

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
			'Sbu',
			'Sbu.lob',
		])->select('*', DB::raw('IF(outlets.amount_eligible = 1,"Yes","No") as amount_eligible'), DB::raw('IF(outlets.deleted_at IS NULL,"Active","Inactive") as status'))
			->withTrashed()
			->find($outlet_id);
		if (!$outlet) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Outlet not found'];
			return response()->json($this->data);
		}

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

}
