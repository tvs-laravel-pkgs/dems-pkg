<?php

namespace Uitoux\EYatra;

use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\LocalTripVisitDetail;
use Validator;

class LocalTrip extends Model {
	use SoftDeletes;
	protected $table = 'local_trips';

	public function visitDetails() {
		return $this->hasMany('Uitoux\EYatra\LocalTripVisitDetail', 'trip_id');
	}

	public function getStartDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}
	public function getEndDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}
	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}

	public function purpose() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'purpose_id');
	}

	public function status() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'status_id');
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
				'purpose',
				'visitDetails',
				'visitDetails.expenseAttachments',
			])->find($trip_id);

			if (!$trip) {
				$data['success'] = false;
				$data['message'] = 'Trip not found';
			}
		}

		$grade = Auth::user()->entity;

		$grade_eligibility = DB::table('grade_advanced_eligibility')->select('local_trip_amount')->where('grade_id', $grade->grade_id)->first();

		if ($grade_eligibility) {
			$beta_amount = $grade_eligibility->local_trip_amount;
		} else {
			$beta_amount = 0;
		}
		$data['beta_amount'] = $beta_amount;

		$data['extras'] = [
			'city_list' => NCity::getList(),
			'travel_mode_list' => Entity::join('local_travel_mode_category_type', 'local_travel_mode_category_type.travel_mode_id', 'entities.id')->select('entities.name', 'entities.id')->where('entities.company_id', Auth::user()->company_id)->where('entities.entity_type_id', 503)->get()->prepend(['id' => '', 'name' => 'Select Travel Mode']),
			'eligible_travel_mode_list' => DB::table('local_travel_mode_category_type')->where('category_id', 3561)->pluck('travel_mode_id')->toArray(),
			'purpose_list' => DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Purpose']),
		];
		$data['trip'] = $trip;

		$data['success'] = true;
		$data['eligible_date'] = $eligible_date = date("Y-m-d", strtotime("-10 days"));

		return response()->json($data);
	}

	public static function saveTrip($request) {
		// dd($request->all());
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

			if (!empty($request->attachment_removal_ids)) {
				$attachment_removal_ids = json_decode($request->attachment_removal_ids, true);
				Attachment::whereIn('id', $attachment_removal_ids)->delete();
			}

			DB::beginTransaction();
			if (!$request->id) {
				$trip = new LocalTrip;
				$trip->created_by = Auth::user()->id;
				$trip->created_at = Carbon::now();
				$trip->updated_at = NULL;
				$activity['activity'] = "add";
				$trip->status_id = 3540; //Manager Approval Pending
			} else {
				$trip = LocalTrip::find($request->id);
				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();
				$trip_visit_details = LocalTripVisitDetail::where('trip_id', $request->id)->count();
				if ($trip->status_id == 3541) {
					$trip->status_id = 3543;
				} elseif ($trip->status_id == 3542) {
					$trip->status_id = 3540;
				} elseif ($trip->status_id == 3545) {
					$trip->status_id = 3543;
				} else {
					$trip->status_id = 3540;
				}
				LocalTripVisitDetail::where('trip_id', $request->id)->forceDelete();
				$activity['activity'] = "edit";

			}

			$employee = Employee::where('id', Auth::user()->entity->id)->first();
			$trip->purpose_id = $request->purpose_id;
			$trip->start_date = date('Y-m-d', strtotime($request->start_date));
			$trip->end_date = date('Y-m-d', strtotime($request->end_date));
			$trip->description = $request->description;
			$trip->employee_id = Auth::user()->entity->id;
			$trip->beta_amount = $request->total_beta_amount;
			$trip->other_amount = $request->total_other_amount;
			$trip->claim_amount = $request->total_claim_amount;
			$trip->save();

			$trip->number = 'TRP' . $trip->id;
			$trip->save();
			$activity['entity_id'] = $trip->id;
			$activity['entity_type'] = 'trip';
			$activity['details'] = 'Local Trip is Added';

			//SAVING VISITS DETAILS
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
					// $visit->amount = $visit_data['amount'];
					$visit->extra_amount = $visit_data['extra_amount'];
					$visit->description = $visit_data['description'];
					$visit->created_by = Auth::user()->id;
					$visit->created_at = Carbon::now();
					$visit->save();

					//SAVE BOARDING ATTACHMENT
					$item_images = storage_path('app/public/local-trip/attachments/');
					Storage::makeDirectory($item_images, 0777);
					if (!empty($visit_data['attachments'])) {

						$attachement = $visit_data['attachments'];
						$name = $attachement->getClientOriginalName();
						$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
						$value = rand(1, 100);
						$extension = $attachement->getClientOriginalExtension();
						$name = $value . '-' . $file_name;
						$attachement->move(storage_path('app/public/local-trip/attachments/'), $name);
						$attachement_file = new Attachment;
						$attachement_file->attachment_of_id = 3186;
						$attachement_file->attachment_type_id = 3186;
						$attachement_file->entity_id = $visit->id;
						$attachement_file->name = $name;
						$attachement_file->save();
					}

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

	public static function getFilterData() {

		$data = [];
		$data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
				->leftJoin('users', 'users.entity_id', 'employees.id')
				->where('users.user_type_id', 3121)
				->where('employees.reporting_to_id', Auth::user()->entity_id)
				->where('employees.company_id', Auth::user()->company_id)
				->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
		$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 531)->where(DB::raw('LOWER(name)'), '!=', strtolower("New"))->get())->prepend(['id' => '-1', 'name' => 'Select Status']);

		$data['outlet_list'] = collect(Outlet::select('name', 'id')->get())->prepend(['id' => '-1', 'name' => 'Select Outlet']);

		$data['success'] = true;
		//dd($data);
		return response()->json($data);
	}

	public static function getViewData($trip_id) {
		$data = [];
		$trip = LocalTrip::with([
			'visitDetails' => function ($q) {
				$q->orderBy('local_trip_visit_details.travel_date');
			},
			'visitDetails.travelMode',
			'visitDetails.expenseAttachments',
			'employee',
			'employee.user',
			'employee.outlet',
			'employee.sbu',
			'employee.sbu.lob',
			'employee.manager',
			'employee.manager.user',
			'employee.user',
			'employee.designation',
			'employee.grade',
			'employee.grade.gradeEligibility',
			'purpose',
			'status',
		])
			->find($trip_id);

		if (!$trip) {
			$data['success'] = false;
			$data['message'] = 'Trip not found';
			$data['errors'] = ['Trip not found'];
			return response()->json($data);
		}

		$days = LocalTrip::select(DB::raw('DATEDIFF(end_date,start_date)+1 as days'))->where('id', $trip_id)->first();
		$trip->days = $days->days;
		$trip->purpose_name = $trip->purpose->name;
		$trip->status_name = $trip->status->name;
		$current_date = strtotime(date('d-m-Y'));
		$claim_date = $trip->employee->grade ? $trip->employee->grade->gradeEligibility->claim_active_days : 5;

		$claim_last_date = strtotime("+" . $claim_date . " day", strtotime($trip->end_date));

		$trip_end_date = strtotime($trip->end_date);

		if ($current_date < $trip_end_date) {
			$data['claim_status'] = 0;
		} else {
			if ($current_date <= $claim_last_date) {
				$data['claim_status'] = 1;
			} else {
				$data['claim_status'] = 0;
			}
		}
		$data['trip'] = $trip;
		$data['success'] = true;

		$data['trip_reject_reasons'] = $trip_reject_reasons = Entity::trip_request_rejection();

		return response()->json($data);

	}

	public static function approveTrip($trip_id) {

		$trip = LocalTrip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Lcoal Trip not found']]);
		}
		$trip_visit_details = LocalTripVisitDetail::where('trip_id', $trip_id)->count();
		if ($trip_visit_details > 0) {
			$trip->status_id = 3544;
		} else {
			$trip->status_id = 3541;
		}

		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Approved by Manager';
		$activity['activity'] = "approve";
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);
		return response()->json(['success' => true, 'message' => 'Trip approved successfully!']);
	}

	public static function rejectTrip($r) {
		$trip = LocalTrip::find($r->trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Local Trip not found']]);
		}
		$trip->rejection_id = $r->reject_id;
		$trip->rejection_remarks = $r->remarks;
		$trip_visit_details = LocalTripVisitDetail::where('trip_id', $r->trip_id)->count();
		if ($trip_visit_details > 0) {
			$trip->status_id = 3545;
		} else {
			$trip->status_id = 3542;
		}
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['activity'] = "reject";
		$activity['details'] = 'Trip is Rejected by Manager';
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);

		return response()->json(['success' => true, 'message' => 'Trip rejected successfully!']);
	}

}
