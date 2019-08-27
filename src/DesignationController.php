<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Uitoux\EYatra\Designation;
use Validator;
use Yajra\Datatables\Datatables;

class DesignationController extends Controller {
	public function listEYatraDesignation(Request $r) {
		$designations = Designation::withTrashed()
			->leftjoin('entities as grade', 'grade.id', 'designations.grade_id')
			->select(
				'designations.id',
				'designations.name',
				DB::raw('IF(grade.name IS NULL,"---",grade.name) as grade_name'),
				'designations.deleted_at',
				DB::raw('IF(designations.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->orderBy('designations.id', 'asc');

		return Datatables::of($designations)
			->addColumn('action', function ($designations) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');

				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '
				<a href="#!/eyatra/designation/edit/' . $designations->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
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
		$this->data['grade_list'] = $grade_list = Entity::select('name', 'id')->where('entity_type_id', 500)->where('company_id', Auth::user()->company_id)->get();
		$this->data['success'] = true;
		$this->data['designation'] = $designation;

		return response()->json($this->data);
	}

	public function saveEYatraDesignation(Request $request) {
		//validation
		//dd($request->all());
		try {
			$error_messages = [

				'name.required' => 'Designation Name is required',
				'name.unique' => 'Designation Name has already been taken',

			];
			$validator = Validator::make($request->all(), [

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
			$designation->company_id = Auth::user()->company_id;
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

	public function deleteEYatraDesignation($designation_id) {
		$designation = Designation::withTrashed()->where('id', $designation_id)->first();
		$designation->forceDelete();
		if (!$designation) {
			return response()->json(['success' => false, 'errors' => ['Designation not found']]);
		}
		return response()->json(['success' => true]);
	}

}
