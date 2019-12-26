<?php

namespace Uitoux\EYatra;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class LocalTrip extends Model {
	use SoftDeletes;
	protected $table = 'local_trips';

	public function visitDetails() {
		return $this->hasMany('Uitoux\EYatra\LocalTripVisitDetail', 'trip_id');
	}

	public static function getLocalTripFormData($trip_id) {
		$data = [];
		if (!$trip_id) {
			$data['action'] = 'New';
			$trip = new LocalTrip;
			$trip->visit_details = [];
			$data['success'] = true;
		} else {
			$data['action'] = 'Edit';
			$data['success'] = true;

			// $trip = LocalTrip::find($trip_id);

			$trip = LocalTrip::withTrashed()->with([
				'visitDetails',
			])->find($trip_id);

			if (!$trip) {
				$data['success'] = false;
				$data['message'] = 'Trip not found';
			}
		}

		$grade = Auth::user()->entity;

		$data['extras'] = [
			'city_list' => NCity::getList(),
			'purpose_list' => DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Purpose']),
		];
		$data['trip'] = $trip;

		$data['success'] = true;
		$data['eligible_date'] = $eligible_date = date("Y-m-d", strtotime("-10 days"));

		return response()->json($data);
	}

	public static function saveTrip($request) {
		try {
			//validation
			$validator = Validator::make($request->all(), [
				'purpose_id' => [
					'required',
				],
				'start_date' => [
					'required',
				],
				'end_date' => [
					'required',
				],

			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation Errors',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$trip = new LocalTrip;
				$trip->created_by = Auth::user()->id;
				$trip->created_at = Carbon::now();
				$trip->updated_at = NULL;
				$activity['activity'] = "add";
			} else {
				$trip = LocalTrip::find($request->id);
				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();
				$trip->visitDetails()->delete();
				$activity['activity'] = "edit";

			}
			$employee = Employee::where('id', Auth::user()->entity->id)->first();

			$trip->purpose_id = $request->purpose_id;
			$trip->start_date = date('Y-m-d', strtotime($request->start_date));
			$trip->end_date = date('Y-m-d', strtotime($request->end_date));
			$trip->description = $request->description;
			$trip->employee_id = Auth::user()->entity->id;
			if ($employee->self_approve == 1) {
				$trip->status_id = 3028; //Manager Approved
			} else {
				$trip->status_id = 3021; //Manager Approval Pending
			}
			$trip->save();

			$trip->number = 'TRP' . $trip->id;
			$trip->save();
			$activity['entity_id'] = $trip->id;
			$activity['entity_type'] = 'trip';
			$activity['details'] = 'Trip is Added';
			//SAVING VISITS
			if ($request->trip_detail) {
				$visit_count = count($request->trip_detail);
				$i = 0;
				foreach ($request->trip_detail as $key => $visit_data) {

					$visit = new LocalTripVisitDetail;
					$visit->trip_id = $trip->id;
					$visit->travel_mode_id = $visit_data['travel_mode_id'];
					$visit->travel_date = date('Y-m-d', strtotime($visit_data['travel_date']));
					$visit->from_place = $visit_data['from_place'];
					$visit->to_place = $visit_data['to_place'];
					$visit->amount = $visit_data['amount'];
					$visit->extra_amount = $visit_data['extra_amount'];
					$visit->description = $visit_data['description'];
					$visit->created_by = Auth::user()->id;
					$visit->created_at = Carbon::now();
					$visit->save();
					$i++;
				}
			}
			DB::commit();

			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Trip added successfully!', 'trip' => $trip]);
			} else {
				return response()->json(['success' => true, 'message' => 'Trip updated successfully!', 'trip' => $trip]);
			}

		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}
