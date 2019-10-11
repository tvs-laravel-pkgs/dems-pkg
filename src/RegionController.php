<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Region;
use Validator;
use Yajra\Datatables\Datatables;

class RegionController extends Controller {
	public function listEYatraRegion(Request $r) {
		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;
		}
		$regions = Region::withTrashed()
			->join('nstates', 'nstates.id', 'regions.state_id')
			->select(
				'regions.id',
				'regions.code',
				'regions.name',
				DB::raw('COALESCE(nstates.name, "--") as state_name'),
				DB::raw('IF(regions.deleted_at IS NULL, "Active","Inactive") as status')
			)
			->where('company_id', Auth::user()->company_id)
			->where(function ($query) use ($r) {
				if ($r->get('state_id')) {
					$query->where("nstates.id", $r->get('state_id'))->orWhere(DB::raw("-1"), $r->get('state_id'));
				}
			})
			->where(function ($query) use ($r, $status) {
				if ($status == '2') {
					$query->whereNull('regions.deleted_at');
				} elseif ($status == '1') {
					$query->whereNotNull('regions.deleted_at');
				}
			})
			->orderBy('regions.id', 'desc');

		return Datatables::of($regions)
			->addColumn('action', function ($region) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/region/edit/' . $region->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/region/view/' . $region->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_region"
				onclick="angular.element(this).scope().deleteRegion(' . $region->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($region) {
				if ($region->status == 'Inactive') {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}
			})
			->make(true);
	}

	public function eyatraRegionFormData($region_id = NULL) {

		if (!$region_id) {
			$this->data['action'] = 'Add';
			$region = new Region;
			$this->data['success'] = true;
			$this->data['status'] = 'Active';
		} else {
			$this->data['action'] = 'Edit';
			$region = Region::withTrashed()->with('state')->find($region_id);
			if (!$region) {
				$this->data['success'] = false;
				$this->data['message'] = 'Region not found';
			}
			if ($region->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
			$this->data['success'] = true;
		}
		$country_list = collect(NCountry::select('name', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$state_list = [];
		$this->data['extras'] = [
			'country_list' => $country_list,
			'state_list' => $state_list,
		];
		$this->data['region'] = $region;

		return response()->json($this->data);
	}

	public function saveEYatraRegion(Request $request) {
		// dd($request->all());
		//validation
		try {
			$error_messages = [
				'name.required' => "Region Name is Required",
				'code.required' => "Region Code is Required",
				'name.unique' => "Region Name is already taken",
				'code.unique' => "Region Code is already taken",
			];

			$validator = Validator::make($request->all(), [
				'code' => [
					'unique:regions,code,' . $request->id,
					'required:true',
				],
				'name' => [
					'required:true',
					'unique:regions,name,' . $request->id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$region = new Region;
				$region->created_by = Auth::user()->id;
				$region->created_at = Carbon::now();
				$region->updated_at = NULL;
			} else {
				$region = Region::withTrashed()->find($request->id);
				$region->updated_by = Auth::user()->id;
				$region->updated_at = Carbon::now();
			}
			if ($request->status == 'Inactive') {
				$region->deleted_at = date('Y-m-d H:i:s');
				$region->deleted_by = Auth::user()->id;
			} else {
				$region->deleted_by = NULL;
				$region->deleted_at = NULL;
			}
			$region->company_id = Auth::user()->company_id;
			$region->fill($request->all());
			$region->save();
			$activity['entity_id'] = $region->id;
			$activity['entity_type'] = "Regions";
			$activity['details'] = empty($request->id) ? "Region is added" : "Region is updated";
			$activity['activity'] = empty($request->id) ? "add" : "edit";
			$activity_log = ActivityLog::saveLog($activity);
			DB::commit();
			// return response()->json(['success' => true]);
			if (empty($request->id)) {

				return response()->json(['success' => true, 'message' => ['Region Added Successfully']]);
			} else {

				return response()->json(['success' => true, 'message' => ['Region Updated Successfully']]);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraRegion($region_id) {

		$region = Region::withTrashed()->with([
			'state',
			'state.country',
		])
			->find($region_id);
		if (!$region) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Region not found'];
			return response()->json($this->data);
		}
		$this->data['region'] = $region;
		$this->data['success'] = true;
		return response()->json($this->data);
	}
	public function eyatraRegionFilterData() {
		$option = new NState;
		$option->name = 'Select State';
		$option->id = null;
		/*$this->data['state_list'] = $state_list = NState::select('name', 'id')
				->get()->prepend($option);
			// dd($state_list);
		*/

		$this->data['state_list'] = $state_list = collect(NState::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select State']);
		$this->data['status_list'] = array(
			array('name' => "Select Status", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Active", 'id' => "2"),
			array('name' => "Inactive", 'id' => "1"),
		);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraRegion($region_id) {
		$region = Region::withTrashed()->where('id', $region_id)->first();
		$activity['entity_id'] = $region->id;
		$activity['entity_type'] = "Regions";
		$activity['details'] = "Regions is deleted";
		$activity['activity'] = "delete";
		$activity_log = ActivityLog::saveLog($activity);
		$region->forceDelete();
		if (!$region) {
			return response()->json(['success' => false, 'errors' => ['Region not found']]);
		}
		return response()->json(['success' => true]);
	}

	public function getStateByCountry(Request $request) {
		if (!empty($request->country_id)) {
			$state_list = collect(NState::where('country_id', $request->country_id)->select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select State']);
		} else {
			$state_list = [];
		}
		return response()->json(['state_list' => $state_list]);
	}

}
