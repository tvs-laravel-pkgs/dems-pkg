<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Designation;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NCity;
use Validator;
use Yajra\Datatables\Datatables;

class FinanceEmployeeClaimController extends Controller {
	public function listEYatraFinanceEmployeeClaim(Request $r) {
		$designations = Designation::withTrashed()->select(
			'designations.id',
			'designations.code',
			'designations.name',
			'designations.deleted_at',
			DB::raw('IF(designations.deleted_at IS NULL,"Active","Inactive") as status')
		)
			->orderBy('designations.name', 'asc');

		return Datatables::of($designations)
			->addColumn('action', function ($designations) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/designation/edit/' . $designations->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/designation/view/' . $designations->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_state"
				onclick="angular.element(this).scope().deleteDesignationConfirm(' . $designations->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($designations) {
				if ($designations->deleted_at) {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}

			})
			->make(true);
	}

	public function eyatraFinanceEmployeeClaimFormData($trip_id = NULL) {
		//dd($trip_id);
		/*if (!$trip_id) {
			$this->data['success'] = false;
			$this->data['message'] = 'Trip not found';
			$trip='';
		} else {
			$trip = Trip::with(
			'visits',
			'purpose',
			'lodgings',
			'boardings',
			'localTravels',
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.selfBooking',
			'visits.agent',
			'visits.status',
			'visits.attachments'
			)->find($trip_id);
			//dd($trip);

			if (!$trip) {
			$this->data['success'] = false;
			$this->data['message'] = 'Trip not found';
			}
			$this->data['success'] = true;
		}*/

		$this->data['extras'] = [
			'purpose_list' => Entity::uiPurposeList(),
			'travel_mode_list' => Entity::uiTravelModeList(),
			'city_list' => NCity::getList(),
			'state_type_list' => Config::getLodgeStayTypeList(),
		];
		$this->data['trip'] = '';

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
					'required:true',
					Rule::unique('designations')->ignore($request->id),
				],

				'name' => [
					'required:true',
					Rule::unique('designations')->ignore($request->id),
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
				$designation->deleted_at = Carbon::now();
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

	public function viewEYatraDesignation($designation_id) {
		$designation = Designation::select('*', DB::raw('IF(designations.deleted_at IS NULL,"Active","Inactive") as status'))
			->withTrashed()
			->find($designation_id);
		$this->data['action'] = 'View';
		if (!$designation) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Designation not found'];
			return response()->json($this->data);
		}
		//dd($designation);
		$this->data['designation'] = $designation;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraDesignation($designation_id) {
		$designation = Designation::withTrashed()->where('id', $designation_id)->first();
		$designation->forceDelete();
		if (!$designation) {
			return response()->json(['success' => false, 'errors' => ['Designation not found']]);
		}
		return response()->json(['success' => true]);
	}

}
