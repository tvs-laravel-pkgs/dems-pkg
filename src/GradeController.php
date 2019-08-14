<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Entity;
use Validator;
use Yajra\Datatables\Datatables;

class GradeController extends Controller {
	public function listEYatraGrade(Request $r) {
		$entity = Entity::withTrashed()->select('entities.id', 'entities.deleted_at', 'entities.name as grade_name', DB::RAW('count(DISTINCT(grade_local_travel_mode.local_travel_mode_id)) as travel_count'), DB::RAW('count(DISTINCT(grade_expense_type.expense_type_id)) as expense_count'), DB::RAW('count(DISTINCT(grade_trip_purpose.trip_purpose_id)) as trip_count'))
			->leftjoin('grade_local_travel_mode', 'grade_local_travel_mode.grade_id', 'entities.id')
			->leftjoin('grade_expense_type', 'grade_expense_type.grade_id', 'entities.id')
			->leftjoin('grade_trip_purpose', 'grade_trip_purpose.grade_id', 'entities.id')
			->where('entities.entity_type_id', 500)
			->where('entities.company_id', Auth::user()->company_id)
			->groupBy('entities.id')
		// ->get()
		;
		// dd($entity);
		return Datatables::of($entity)
			->addColumn('status', function ($entity) {
				if ($entity->deleted_at) {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}

			})
			->addColumn('action', function ($entity) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/grade/edit/' . $entity->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/grade/view/' . $entity->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>

				<a href="javascript:;" data-toggle="modal" data-target="#delete_grade"
				onclick="angular.element(this).scope().deleteGrade(' . $entity->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})

			->make(true);
	}

	public function eyatraGradeFormData($entity_id = NULL) {

		$expense_type_list = Config::expenseList();
		$travel_purpose_list = Entity::purposeList();
		$travel_types_list = Entity::travelModeList();
		$eligibility_type_list = Entity::eligibilityType();
		if (!$entity_id) {
			$this->data['action'] = 'Add';
			$entity = new Entity;
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$entity = Entity::withTrashed()->find($entity_id);

			if (count($entity->expenseTypes) > 0) {
				foreach ($entity->expenseTypes as $expense_type) {
					$expense_type_list[$expense_type->id]->checked = true;
					$expense_type_list[$expense_type->id]->eligible_amount = $expense_type->pivot->eligible_amount;
				}
			}

			if (count($entity->tripPurposes) > 0) {
				foreach ($entity->tripPurposes as $trip_purpose) {
					$travel_purpose_list[$trip_purpose->id]->checked = true;
				}
			}
			if (count($entity->localTravelModes) > 0) {
				foreach ($entity->localTravelModes as $travel_type) {
					$travel_types_list[$travel_type->id]->checked = true;
				}
			}

			$this->data['success'] = true;
		}
		$this->data['extras'] = [
			'expense_type_list' => $expense_type_list,
			'travel_purpose_list' => $travel_purpose_list,
			'travel_types_list' => $travel_types_list,
			'eligibility_type_list' => $eligibility_type_list,
		];
		$this->data['entity'] = $entity;

		return response()->json($this->data);
	}

	public function saveEYatraGrade(Request $request) {
		//validation
		// dd($request->all());
		try {

			$error_messages = [
				'grade_name.unique' => "Grade Name is already taken",
			];

			$validator = Validator::make($request->all(), [
				'grade_name' => 'required|unique:entities,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',entity_type_id,500',
			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();

			if (!$request->id) {
				$grade = new Entity;
				$grade->created_by = Auth::user()->id;
				$grade->created_at = Carbon::now();
				$grade->updated_at = NULL;

			} else {
				$grade = Entity::withTrashed()->find($request->id);
				$grade->expenseTypes()->sync([]);
				$grade->tripPurposes()->sync([]);
				$grade->localTravelModes()->sync([]);
				$grade->updated_by = Auth::user()->id;
				$grade->updated_at = Carbon::now();

			}
			if ($request->status == 'Inactive') {
				$grade->deleted_by = Auth::user()->id;
				$grade->deleted_at = Carbon::now();
			} else {
				$grade->deleted_by = NULL;
				$grade->deleted_at = NULL;
			}
			$grade->company_id = Auth::user()->company_id;
			$grade->name = $request->grade_name;
			$grade->entity_type_id = 500;
			$grade->save();

			//Save Expense Mode
			if (count($request->expense_types) > 0) {
				foreach ($request->expense_types as $expense_type_id => $pivot_data) {
					if (!isset($pivot_data['id'])) {
						continue;
					}
					unset($pivot_data['id']);
					$grade->expenseTypes()->attach($expense_type_id, $pivot_data);
				}
			}
			if (!empty($request->checked_purpose_list)) {
				// dd($request->checked_purpose_list);
				$grade->tripPurposes()->sync($request->checked_purpose_list);
			}

			if (!empty($request->checked_travel_mode)) {
				$grade->localTravelModes()->sync($request->checked_travel_mode);
			}

			DB::commit();
			$request->session()->flash('success', 'Grade Updated successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraGrade($grade_id) {

		$this->data['grade'] = $entity = Entity::withTrashed()->find($grade_id);
		$this->data['expense_type_list'] = $expense_type_list = $entity->expenseTypes;
		// dd($expense_type_list);
		$this->data['travel_purpose_list'] = $travel_purpose_list = $entity->tripPurposes;
		$this->data['localtravel_list'] = $localtravel_list = $entity->localTravelModes;
		$this->data['action'] = 'View';
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraGrade($grade_id) {
		$grade = Entity::where('id', $grade_id)->forceDelete();
		if (!$grade) {
			return response()->json(['success' => false, 'errors' => ['Grade Not Found']]);
		}
		return response()->json(['success' => true]);
	}

}
