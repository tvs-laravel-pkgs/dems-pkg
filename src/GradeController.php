<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\Trip;
use Validator;
use Yajra\Datatables\Datatables;

class GradeController extends Controller {
	public function listEYatraGrade(Request $r) {
		$entity = Entity::withTrashed()->select('entities.id as grade_id', 'entities.name as grade_name', DB::RAW('count(grade_local_travel_mode.local_travel_mode_id) as travel_count'), DB::RAW('count(grade_expense_type.expense_type_id) as expense_count'), DB::RAW('count(grade_trip_purpose.trip_purpose_id) as trip_count'))
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
			->addColumn('expense_count', function ($entity) {

			})
			->addColumn('travel_count', function ($entity) {

			})
			->addColumn('trip_count', function ($entity) {

			})
			->addColumn('action', function ($entity) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/grade/edit/' . $entity->grade_id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/trip/view/' . $entity->grade_id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_emp"
				onclick="angular.element(this).scope().deleteTrip(' . $entity->grade_id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})
			->make(true);
	}

	public function eyatraGradeFormData($entity_id = NULL) {

		if (!$entity_id) {
			$this->data['action'] = 'New';
			$entity = new entity;

			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$entity = Entity::find($entity_id);
			// dd($entity_id);
			// if (!$trip) {
			// 	$this->data['success'] = false;
			// 	$this->data['message'] = 'Trip not found';
			// }
			// ->select('expense_type_id','eligible_amount')->get()
			$this->data['selected_expense_types'] = $entity->expenseTypes()->pluck('expense_type_id')->toArray();
			$this->data['selected_purposeList'] = $entity->tripPurposes()->pluck('trip_purpose_id')->toArray();
			$this->data['selected_localTravelModes'] = $entity->localTravelModes()->pluck('local_travel_mode_id')->toArray();
			// $this->data['selected_expense_types'] = $entity->expenseTypes()->select('eligible_amount', 'expense_type_id')->toArray();
			// $this->data['selected_expense_types'] = $entity->expenseTypes()->pluck('eligible_amount', 'expense_type_id')->toArray();
			$this->data['success'] = true;
		}
		$this->data['extras'] = [
			'expense_type' => Config::expenseList(),
			'purpose_list' => Entity::purposeList(),
			'travel_mode_list' => Entity::travelModeList(),
		];
		$this->data['entity'] = $entity;

		return response()->json($this->data);
	}

	public function saveEYatraGrade(Request $request) {
		//validation
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'grade_name' => [
					'required',
				],
			]);

			$validator = Validator::make($request->all(), [
				"grade_name" => [
					Rule::unique('entities')->ignore($request->id),
					'max:191',
				],
			]);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();

			if (!$request->id) {
				$grade_details = new Entity;
				$grade_details->created_by = Auth::user()->id;
				$grade_details->created_at = Carbon::now();
				$grade_details->updated_at = NULL;

			} else {
				$grade_details = Entity::find($request->id);
				$grade_details->expenseTypes()->sync([]);
				$grade_details->tripPurposes()->sync([]);
				$grade_details->localTravelModes()->sync([]);
				$grade_details->updated_by = Auth::user()->id;
				$grade_details->updated_at = Carbon::now();

			}
			if ($request->status == 'Inactive') {
				$grade_details->deleted_by = Auth::user()->id;
				$grade_details->deleted_at = Carbon::now();
			}
			$grade_details->company_id = Auth::user()->company_id;
			$grade_details->name = $request->grade_name;
			$grade_details->entity_type_id = 500;
			$grade_details->save();

			//Save Expense Mode
			if (!empty($request->checked_expense_type)) {
				foreach ($request->checked_expense_type as $key => $value) {
					$grade_details->expenseTypes()->attach($request->checked_expense_type[$key], ['eligible_amount' => $request->selected_expense_amounts[$key]]);
				}
			}
			if (!empty($request->checked_expense_type)) {
				$grade_details->tripPurposes()->sync($request->checked_purpose_list);
			}

			if (!empty($request->checked_expense_type)) {
				$grade_details->localTravelModes()->sync($request->checked_travel_mode);
			}

			DB::commit();
			$request->session()->flash('success', 'Grade saved successfully!');
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraGrade($grade_id) {

		$trip = Trip::with([
			'visits',
			'visits.fromCity',
			'visits.toCity',
			'visits.travelMode',
			'visits.bookingMethod',
			'visits.bookingStatus',
			'visits.agent',
			'visits.status',
			'visits.managerVerificationStatus',
			'employee',
			'purpose',
			'status',
		])
			->find($trip_id);
		if (!$trip) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Trip not found'];
			return response()->json($this->data);
		}
		$start_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$end_date = $trip->visits()->select(DB::raw('DATE_FORMAT(MIN(visits.date),"%d/%m/%Y") as start_date'))->first();
		$trip->start_date = $start_date->start_date;
		$trip->end_date = $start_date->end_date;
		$this->data['trip'] = $trip;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraGrade($grade_id) {
		$trip = Trip::where('id', $trip_id)->delete();
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		return response()->json(['success' => true]);
	}

}
