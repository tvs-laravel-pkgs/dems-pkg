<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\GradeAdvancedEligiblity;
use Validator;
use Yajra\Datatables\Datatables;

class GradeController extends Controller {

	public function eyatraGradeFilter() {
		$this->data['advanced_eligibility_list'] = $advanced_eligibility_list = array(
			array('name' => "Select Advanced Eligibility", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Yes", 'id' => "1"),
			array('name' => "No", 'id' => "0"),
		);
		$this->data['status_list'] = array(
			array('name' => "Select Status", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Active", 'id' => "2"),
			array('name' => "In Active", 'id' => "1"),
		);

		return response()->json($this->data);
	}
	public function listEYatraGrade(Request $r) {

		if (isset($r->advanced_eligibility)) {

			$advanced_eligibility = $r->advanced_eligibility;
		} else {
			$advanced_eligibility = null;
		}

		if (!empty($r->status)) {
			$status = $r->status;
		} else {
			$status = null;
		}
		$grade_list = Entity::withTrashed()->select('entities.id', 'entities.deleted_at', 'entities.name as grade_name', DB::RAW('count(DISTINCT(grade_local_travel_mode.local_travel_mode_id)) as local_travel_count'), DB::RAW('count(DISTINCT(grade_travel_mode.travel_mode_id)) as travel_count'), DB::RAW('count(DISTINCT(grade_expense_type.expense_type_id)) as expense_count'), DB::RAW('count(DISTINCT(grade_trip_purpose.trip_purpose_id)) as trip_count'),
			// DB::raw('CASE WHEN grade_advanced_eligibility.advanced_eligibility == 0 THEN No ELSE Yes END as grade_eligiblity')
			DB::raw('IF(grade_advanced_eligibility.advanced_eligibility = 0, "No", "Yes") as grade_eligiblity')
		)
			->where(function ($query) use ($r, $advanced_eligibility) {
				if ($advanced_eligibility == '1') {
					$query->where('grade_advanced_eligibility.advanced_eligibility', $advanced_eligibility);
					// $query->where('grade_advanced_eligibility.advanced_eligibility = 1');
				} elseif ($advanced_eligibility == '0') {
					$query->where('grade_advanced_eligibility.advanced_eligibility', $advanced_eligibility);
				}
			})
			->where(function ($query) use ($r, $status) {
				if ($status == '2') {
					$query->whereNull('entities.deleted_at');
				} elseif ($status == '1') {
					$query->whereNotNull('entities.deleted_at');
				}
			})
			->leftjoin('grade_local_travel_mode', 'grade_local_travel_mode.grade_id', 'entities.id')
			->leftjoin('grade_travel_mode', 'grade_travel_mode.grade_id', 'entities.id')
			->leftjoin('grade_expense_type', 'grade_expense_type.grade_id', 'entities.id')
			->leftjoin('grade_trip_purpose', 'grade_trip_purpose.grade_id', 'entities.id')
			->leftjoin('grade_advanced_eligibility', 'grade_advanced_eligibility.grade_id', 'entities.id')
			->where('entities.entity_type_id', 500)
			->where('entities.company_id', Auth::user()->company_id)
			->groupBy('entities.id')
			->orderby('entities.id', 'desc')
		// ->get()
		;
		// dd($grade_list);
		return Datatables::of($grade_list)
			->addColumn('status', function ($grade_list) {
				if ($grade_list->deleted_at) {
					return '<span style="color:#ea4335">In Active</span>';
				} else {
					return '<span style="color:#63ce63">Active</span>';
				}

			})
			->addColumn('action', function ($grade_list) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';
				$edit_class = "visibility:hidden";
				if (Entrust::can('eyatra-grade-edit')) {
					$edit_class = "";
				}
				$delete_class = "visibility:hidden";
				if (Entrust::can('eyatra-grade-delete')) {
					$delete_class = "";
				}

				$action .= '<a style="' . $edit_class . '" href="#!/grade/edit/' . $grade_list->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a> ';
				$action .= '<a href="#!/grade/view/' . $grade_list->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a> ';
				$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_grade"
				onclick="angular.element(this).scope().deleteGrade(' . $grade_list->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

				return $action;
			})

			->make(true);
	}

	public function eyatraGradeFormData($grade_id = NULL) {

		$expense_type_list = Config::expenseList();
		$travel_purpose_list = Entity::purposeList();
		$travel_types_list = Entity::travelModeList();
		$local_travel_types_list = Entity::localTravelModeList();
		$city_category_list = Entity::cityCategoryList();
		if (!$grade_id) {
			$this->data['action'] = 'Add';
			$grade = new Entity;
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$grade = Entity::withTrashed()->find($grade_id);
			if (count($grade->expenseTypes) > 0) {
				foreach ($grade->expenseTypes as $expense_type) {
					$expense_type_list[$expense_type->id]->checked = true;
					if ($expense_type->pivot->city_category_id) {
						$expense_type_list[$expense_type->id]->{$expense_type->pivot->city_category_id} = $expense_type->pivot->eligible_amount;
					}
				}
			}
			if (count($grade->tripPurposes) > 0) {
				foreach ($grade->tripPurposes as $trip_purpose) {
					$travel_purpose_list[$trip_purpose->id]->checked = true;
				}
			}
			if (count($grade->travelModes) > 0) {
				foreach ($grade->travelModes as $travel_type) {
					$travel_types_list[$travel_type->id]->checked = true;
				}
			}

			if (count($grade->localTravelModes) > 0) {
				foreach ($grade->localTravelModes as $local_travel_type) {
					$local_travel_types_list[$local_travel_type->id]->checked = true;
				}
			}
			$this->data['grade_details'] = GradeAdvancedEligiblity::where('grade_id', $grade_id)->select('advanced_eligibility', 'stay_type_disc', 'deviation_eligiblity', 'claim_active_days', 'travel_advance_limit', 'two_wheeler_limit', 'four_wheeler_limit', 'two_wheeler_per_km', 'four_wheeler_per_km', 'local_trip_amount','outstation_trip_amount')->first();
			$this->data['success'] = true;
		}
		$this->data['extras'] = [
			'expense_type_list' => $expense_type_list,
			'travel_purpose_list' => $travel_purpose_list,
			'travel_types_list' => $travel_types_list,
			'city_category_list' => $city_category_list,
			'local_travel_types_list' => $local_travel_types_list,
		];
		$this->data['grade'] = $grade;
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
				$grade_details = new GradeAdvancedEligiblity;
				// $grade_eligiblity = new GradeAdvancedEligiblity;
				$grade->created_by = Auth::user()->id;
				$grade->created_at = Carbon::now();
				$grade->updated_at = NULL;

			} else {
				$grade = Entity::withTrashed()->find($request->id);
				$grade_details = GradeAdvancedEligiblity::firstOrNew(['grade_id' => $request->id]);
				// $grade_eligiblity = GradeAdvancedEligiblity::find($request->id);
				$grade->expenseTypes()->sync([]);
				$grade->tripPurposes()->sync([]);
				$grade->travelModes()->sync([]);
				$grade->localTravelModes()->sync([]);
				// $grade->gradeEligibility()->sync([]);
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

			$activity['entity_id'] = $grade->id;
			$activity['entity_type'] = "Employee Grade";
			$activity['details'] = empty($request->id) ? "Employee Grade is  Added" : "Employee Grade is updated";
			$activity['activity'] = empty($request->id) ? "Add" : "Edit";
			$activity_log = ActivityLog::saveLog($activity);

			// if ($request->grade_advanced == 'Yes') {
			// 	$request->grade_advanced = 1;
			// } else {
			// 	$request->grade_advanced = 0;
			// }

			//Update Grade Details
			$grade_details->grade_id = $grade->id;
			if ($request->grade_advanced == 'Yes') {
				$grade_details->advanced_eligibility = 1;
			} else {
				$grade_details->advanced_eligibility = 0;
			}
			$grade_details->stay_type_disc = $request->discount_percentage;
			if ($request->deviation_eligiblity == 'Yes') {
				$grade_details->deviation_eligiblity = 1;
			} else {
				$grade_details->deviation_eligiblity = 2;
			}
			$grade_details->claim_active_days = $request->claim_active_days;
			$grade_details->travel_advance_limit = $request->travel_advance_limit;
			$grade_details->two_wheeler_limit = $request->two_wheeler_limit;
			$grade_details->four_wheeler_limit = $request->four_wheeler_limit;
			$grade_details->four_wheeler_per_km = $request->four_wheeler_per_km;
			$grade_details->two_wheeler_per_km = $request->two_wheeler_per_km;
			$grade_details->local_trip_amount = $request->local_trip_amount;
			$grade_details->outstation_trip_amount = $request->outstation_trip_amount;
			$grade_details->save();

			// $grade->gradeEligibility()->sync($request->grade_advanced);

			//Save Expense Mode
			if (count($request->expense_types) > 0) {
				foreach ($request->expense_types as $expense_type_id => $pivot_data) {
					unset($pivot_data['id']);
					// dd($pivot_data);
					foreach ($pivot_data as $city_id => $eligible_amount) {
						if (!empty($eligible_amount['eligible_amount'])) {
							$data = [$expense_type_id => ['eligible_amount' => $eligible_amount['eligible_amount'], 'city_category_id' => $city_id]];
							$grade->expenseTypes()->attach($data);
						}
					}
				}
			}
			if (!empty($request->checked_purpose_list)) {
				$grade->tripPurposes()->sync($request->checked_purpose_list);
			}

			if (!empty($request->checked_travel_mode)) {
				$grade->travelModes()->sync($request->checked_travel_mode);
			}

			if (!empty($request->checked_local_travel_mode)) {
				$grade->localTravelModes()->sync($request->checked_local_travel_mode);
			}

			DB::commit();
			// $request->session()->flash('success', 'Grade Updated successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => ['Grade Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Grade Updated Successfully']]);
			}

			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraGrade($grade_id) {
		$this->data['grade'] = $grade = Entity::withTrashed()->find($grade_id);
		$expense_type_list = Config::expenseList();
		$city_category_list = Entity::cityCategoryList();
		$travel_purpose_list = $grade->tripPurposes;
		$travel_types_list = $grade->travelModes;
		$local_travel_types_list = $grade->localTravelModes;
		if (count($grade->expenseTypes) > 0) {
			foreach ($grade->expenseTypes as $expense_type) {
				$expense_type_list[$expense_type->id]->checked = true;
				if ($expense_type->pivot->city_category_id) {
					$expense_type_list[$expense_type->id]->{$expense_type->pivot->city_category_id} = $expense_type->pivot->eligible_amount;
				}
			}
		}

		$this->data['extras'] = [
			'expense_type_list' => $expense_type_list,
			'travel_purpose_list' => $travel_purpose_list,
			'travel_types_list' => $travel_types_list,
			'city_category_list' => $city_category_list,
			'local_travel_types_list' => $local_travel_types_list,
		];
		$grade_advanced = $grade->gradeEligibility()->where('grade_id', $grade_id)->pluck('advanced_eligibility');
		$this->data['grade_advanced'] = count($grade_advanced) ? 'Yes' : 'No';
		$this->data['action'] = 'View';

		$this->data['grade_details'] = GradeAdvancedEligiblity::where('grade_id', $grade_id)->select('advanced_eligibility', 'stay_type_disc', 'deviation_eligiblity', 'claim_active_days', 'travel_advance_limit', 'two_wheeler_limit', 'four_wheeler_limit', 'two_wheeler_per_km', 'four_wheeler_per_km', 'local_trip_amount','outstation_trip_amount')->first();

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraGrade($grade_id) {
		$grade = Entity::withTrashed()->where('id', $grade_id)->first();
		$activity['entity_id'] = $grade->id;
		$activity['entity_type'] = "Employee Grade";
		$activity['details'] = "Employee Grade is deleted";
		$activity['activity'] = "Delete";
		$activity_log = ActivityLog::saveLog($activity);
		$grade->forceDelete();
		if (!$grade) {
			return response()->json(['success' => false, 'errors' => ['Grade Not Found']]);
		}
		return response()->json(['success' => true]);
	}

}
