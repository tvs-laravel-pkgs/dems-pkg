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
		$regions = Region::withTrashed()
			->join('nstates', 'nstates.id', 'regions.state_id')
			->select(
				'regions.id',
				'regions.code',
				'regions.name',
				DB::raw('COALESCE(nstates.name, "--") as state_name'),
				DB::raw('IF(regions.deleted_at IS NULL, "Active","Inactive") as status')
			)
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
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

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
		$country_list = collect(NCountry::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Country']);
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
			$region->fill($request->all());
			if ($request->status == 'Inactive') {
				$region->deleted_at = date('Y-m-d H:i:s');
				$region->deleted_by = Auth::user()->id;
			} else {
				$region->deleted_by = NULL;
				$region->deleted_at = NULL;
			}
			$region->save();

			DB::commit();
			return response()->json(['success' => true]);
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

	public function deleteEYatraRegion($region_id) {
		$region = Region::withTrashed()->where('id', $region_id)->forcedelete();
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
