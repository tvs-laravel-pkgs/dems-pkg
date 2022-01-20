<?php

namespace Uitoux\EYatra;

use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Uitoux\EYatra\ApprovalLog;
use Uitoux\EYatra\Attachment;
use Uitoux\EYatra\LocalTripVisitDetail;
use Validator;

class LocalTrip extends Model {
	use SoftDeletes;
	protected $table = 'local_trips';

	public function visitDetails() {
		return $this->hasMany('Uitoux\EYatra\LocalTripVisitDetail', 'trip_id');
	}

	public function expense() {
		return $this->hasMany('Uitoux\EYatra\LocalTripExpense', 'trip_id')->orderBy('expense_date','asc');
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

	public function google_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3187)->where('attachment_type_id', 3200);
	}

	public function expenseAttachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3186)->where('attachment_type_id', 3200);
	}

	public function otherExpenseAttachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3188)->where('attachment_type_id', 3200);
	}

	public static function getLocalTripList($request) {
		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename', 'local_trips.status_id',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'purpose.name as purpose',
				'status.name as status'
			)
			->where('local_trips.employee_id', Auth::user()->entity_id)
			->groupBy('local_trips.id')
			->orderBy('local_trips.id', 'desc')
		;

		//FILTERS
		if ($request->number) {
			$trips->where('local_trips.number', 'like', '%' . $request->number . '%');
		}
		if ($request->from_date) {
			$date = date('Y-m-d', strtotime($request->get('from_date')));
			$trips->where("local_trips.start_date", '>=', $date);
		}
		if ($request->to_date) {
			$date = date('Y-m-d', strtotime($request->get('to_date')));
			$trips->where("local_trips.end_date", '<=', $date);
		}
		if ($request->status_ids) {
			$trips->whereIn('local_trips.status_id', json_decode($request->status_ids));
		}
		if ($request->purpose_ids) {
			$trips->where('local_trips.purpose_id', $request->purpose_ids);
		}
		if ($request->future_trip == '1') {
			$current_date = date('Y-m-d');
			$trips->where('local_trips.end_date', '<=', $current_date);
		}

		return $trips;
	}

	public static function getVerficationPendingList($request) {

		$trips = LocalTrip::from('local_trips')
			->join('employees as e', 'e.id', 'local_trips.employee_id')
			->join('entities as purpose', 'purpose.id', 'local_trips.purpose_id')
			->join('configs as status', 'status.id', 'local_trips.status_id')
			->leftJoin('users', 'users.entity_id', 'local_trips.employee_id')
			->where('users.user_type_id', 3121)
			->select(
				'local_trips.id',
				'local_trips.number',
				'e.code as ecode',
				'users.name as ename', 'local_trips.status_id',
				DB::raw('CONCAT(DATE_FORMAT(local_trips.start_date,"%d-%m-%Y"), " to ", DATE_FORMAT(local_trips.end_date,"%d-%m-%Y")) as travel_period'),
				DB::raw('DATE_FORMAT(local_trips.created_at,"%d/%m/%Y %h:%i %p") as created_date'),
				'purpose.name as purpose',
				'status.name as status'
			)
			->whereIN('local_trips.status_id', [3021, 3023])
			->groupBy('local_trips.id')
			->orderBy('local_trips.id', 'desc')
		;

		//FILTERS
		if ($request->number) {
			$trips->where('local_trips.number', 'like', '%' . $request->number . '%');
		}
		if ($request->from_date) {
			$date = date('Y-m-d', strtotime($request->get('from_date')));
			$trips->where("local_trips.start_date", '>=', $date);
		}
		if ($request->to_date) {
			$date = date('Y-m-d', strtotime($request->get('to_date')));
			$trips->where("local_trips.end_date", '<=', $date);
		}
		if ($request->status_ids) {
			$trips->whereIn('local_trips.status_id', json_decode($request->status_ids));
		}
		if ($request->purpose_ids) {
			$trips->where('local_trips.purpose_id', $request->purpose_ids);
		}

		$now = date('Y-m-d');
		$sub_employee_id = AlternateApprove::select('employee_id')
			->where('from', '<=', $now)
			->where('to', '>=', $now)
			->where('alternate_employee_id', Auth::user()->entity_id)
			->get()
			->toArray();
		$ids = array_column($sub_employee_id, 'employee_id');
		array_push($ids, Auth::user()->entity_id);
		if (count($sub_employee_id) > 0) {
			$trips = $trips->whereIn('e.reporting_to_id', $ids); //Alternate MANAGER
		} else {
			$trips = $trips->where('e.reporting_to_id', Auth::user()->entity_id); //MANAGER
		}

		return $trips;
	}

	public static function getLocalTripFormData($trip_id) {
		$data = [];
		if (!$trip_id) {
			$data['action'] = 'New';
			$trip = new LocalTrip;
			$trip->visit_details = [];
			$data['success'] = true;

			$user = Auth::user()->entity;
			if(!$user)
			{
				if (!$trip) {
					$data['success'] = false;
					$data['message'] = 'Employee Grade not found';
				}
			}
			$grade_id = $user->grade_id;
			$employee_id = $user->id;
		} else {
			$data['action'] = 'Edit';
			$data['success'] = true;

			// $trip = LocalTrip::find($trip_id);

			$trip = LocalTrip::withTrashed()->with([
				'purpose',
				'visitDetails',
				'expense',
				'expenseAttachments',
				'otherExpenseAttachments'
			])->find($trip_id);

			if (!$trip) {
				$data['success'] = false;
				$data['message'] = 'Trip not found';
			}

			$grade_id = Employee::where('id',$trip->employee_id)->pluck('grade_id')->first();
			$employee_id = $trip->employee_id;

		}
		$grade_eligibility = DB::table('grade_advanced_eligibility')->select('local_trip_amount')->where('grade_id', $grade_id)->first();

		if ($grade_eligibility) {
			$beta_amount = $grade_eligibility->local_trip_amount;
		} else {
			$beta_amount = 0;
		}
		$data['beta_amount'] = $beta_amount;

		$employee = Employee::select('users.name as name', 'employees.code as code', 'designations.name as designation', 'entities.name as grade', 'employees.grade_id', 'employees.id', 'employees.gender', 'gae.two_wheeler_per_km', 'gae.four_wheeler_per_km','gae.local_trip_amount')
			->leftjoin('grade_advanced_eligibility as gae', 'gae.grade_id', 'employees.grade_id')
			->leftjoin('designations', 'designations.id', 'employees.designation_id')
			->leftjoin('users', 'users.entity_id', 'employees.id')
			->leftjoin('entities', 'entities.id', 'employees.grade_id')
			->where('employees.id', $employee_id)
			->where('users.user_type_id', 3121)->first();
			
		//Get Own Vehicle details
		$vehicle_details = Entity::join('travel_mode_category_type', 'travel_mode_category_type.travel_mode_id', 'entities.id')->where('travel_mode_category_type.category_id', 3400)->where('entities.company_id', Auth::user()->company_id)->where('entities.entity_type_id', 502)->select('entities.name', 'entities.id')->get();
		$values = [];
		foreach ($vehicle_details as $key => $value) {
			$stripped = strtolower(preg_replace('/\s/', '', $value->name));
			if ($stripped == 'twowheeler') {
				$values[$value->id] = $employee->two_wheeler_per_km;
			} elseif ($stripped == 'fourwheeler') {
				$values[$value->id] = $employee->four_wheeler_per_km;
			} else {
				$values[$value->id] = '0';
			}
		}

		$data['travel_values'] = $values;

		$data['extras'] = [
			'travel_mode_list' => collect(Entity::uiClaimTravelModeList()->prepend(['id' => '', 'name' => 'Select Travel Mode'])),
			'eligible_travel_mode_list' => DB::table('local_travel_mode_category_type')->where('category_id', 3561)->pluck('travel_mode_id')->toArray(),
			'purpose_list' => DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_trip_purpose.grade_id', $grade_id)->where('entities.company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Purpose']),
			'travel_values' => $values,
		];
		$data['trip'] = $trip;

		$data['success'] = true;
		$data['eligible_date'] = $eligible_date = date("Y-m-d", strtotime("-10 days"));
		$data['max_eligible_date'] = $max_eligible_date = date("Y-m-d", strtotime("+30 days"));

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

			// if (!empty($request->trip_detail_removal_id)) {
			// 	$local_removal_ids = json_decode($request->trip_detail_removal_id, true);
			// 	LocalTrip::whereIn('id', $local_removal_ids)->forceDelete();
			// }

			DB::beginTransaction();
			if (!$request->id) {
				$trip = new LocalTrip;
				$trip->created_by = Auth::user()->id;
				$trip->created_at = Carbon::now();
				$trip->updated_at = NULL;
				$activity['activity'] = "add";
				$trip->status_id = 3021; //Manager Approval Pending
			} else {
				$trip = LocalTrip::find($request->id);
				$trip->updated_by = Auth::user()->id;
				$trip->updated_at = Carbon::now();
				$trip_visit_details = LocalTripVisitDetail::where('trip_id', $request->id)->count();
				// if ($trip->status_id == 3028) {
				// 	$trip->status_id = 3023;
				// } else
				if ($trip->status_id == 3022) {
					$trip->status_id = 3021;
				} elseif ($trip->status_id == 3024) {
					$trip->status_id = 3023;
				} elseif ($trip->status_id == 3032) {
					$trip->status_id = 3021;
				} else {
					$trip->status_id = 3021;
				}

				LocalTripVisitDetail::where('trip_id', $request->id)->forceDelete();
				LocalTripExpense::where('trip_id', $request->id)->forceDelete();
				$activity['activity'] = "edit";
			}

			//SELF APPROVAL
			$employee = Employee::where('id', Auth::user()->entity->id)->first();
			if ($employee->self_approve == 1) {
				$trip->status_id = 3028;
			}
			if ($request->local_trip_claim) {
				$trip->status_id = 3023;
				if ($employee->self_approve == 1) {
					$trip->status_id = 3034;
				}
			}

			$employee = Employee::where('id', Auth::user()->entity->id)->first();
			$trip->purpose_id = $request->purpose_id;
			$trip->start_date = date('Y-m-d', strtotime($request->start_date));
			$trip->end_date = date('Y-m-d', strtotime($request->end_date));
			$trip->description = $request->description;
			$trip->employee_id = Auth::user()->entity->id;
			$trip->beta_amount = $request->total_beta_amount;
			$trip->travel_amount = $request->total_travel_amount;
			$trip->other_expense_amount = $request->total_expense_amount;
			$trip->claim_amount = $request->total_claim_amount;
			$trip->claimed_date = date('Y-m-d');
			$trip->save();
			$trip->number = 'TRP' . $trip->id;
			$trip->rejection_id = NULL;
			$trip->rejection_remarks = NULL;
			$trip->save();

			if ($request->is_justify_my_trip) {
				$trip->is_justify_my_trip = 1;
			} else {
				$trip->is_justify_my_trip = 0;
			}
			$trip->remarks = $request->remarks;
			$trip->save();

			//STORE GOOGLE ATTACHMENT
			$item_images = storage_path('app/public/trip/local-trip/google_attachments/');
			Storage::makeDirectory($item_images, 0777);
			if ($request->hasfile('google_attachments')) {

				foreach ($request->file('google_attachments') as $key => $attachement) {
					$image = $attachement;
					$extension = $image->getClientOriginalExtension();
					$name = $image->getClientOriginalName();
					$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
					$value = rand(1, 100);
					$extension = $image->getClientOriginalExtension();
					$name = $value . '-' . $file_name;
					$image->move(storage_path('app/public/trip/local-trip/google_attachments/'), $name);
					$attachement_file = new Attachment;
					$attachement_file->attachment_of_id = 3187;
					$attachement_file->attachment_type_id = 3200;
					$attachement_file->entity_id = $trip->id;
					$attachement_file->name = $name;
					$attachement_file->save();
				}

			}

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
					$visit->amount = $visit_data['amount'];
					$visit->description = $visit_data['description'];
					$visit->from_km = $visit_data['from_km'];
					$visit->to_km = $visit_data['to_km'];
					$visit->created_by = Auth::user()->id;
					$visit->created_at = Carbon::now();
					$visit->save();
					$i++;
				}
			}

			if ($request->expense_detail) {
				$visit_count = count($request->expense_detail);
				$i = 0;
				foreach ($request->expense_detail as $key => $expense_data) {
					$visit = new LocalTripExpense;
					$visit->trip_id = $trip->id;
					$visit->expense_date = date('Y-m-d', strtotime($expense_data['expense_date']));
					$visit->amount = $expense_data['amount'];
					$visit->description = $expense_data['description'];
					$visit->created_by = Auth::user()->id;
					$visit->created_at = Carbon::now();
					$visit->save();
					$i++;
				}
			}

			//SAVE TRAVEL EXPENSE ATTACHMENT
			$item_images = storage_path('app/public/trip/local-trip/attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (!empty($request->expense_attachments)) {
				foreach ($request->expense_attachments as $key => $attachement) {
					$image = $attachement;
					$extension = $image->getClientOriginalExtension();
					$name = $image->getClientOriginalName();
					$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
					$value = rand(1, 100);
					$extension = $image->getClientOriginalExtension();
					$name = $trip->id .'-' . $value . '-Travel-expense-' . $file_name;
					$image->move(storage_path('app/public/trip/local-trip/attachments/'), $name);
					$attachement_file = new Attachment;
					$attachement_file->attachment_of_id = 3186;
					$attachement_file->attachment_type_id = 3200;
					$attachement_file->entity_id = $trip->id;
					$attachement_file->name = $name;
					$attachement_file->save();
				}
			}

			//SAVE OTHER EXPENSE ATTACHMENT
			$item_images = storage_path('app/public/trip/local-trip/attachments/');
			Storage::makeDirectory($item_images, 0777);
			if (!empty($request->other_expense_attachments)) {
				foreach ($request->other_expense_attachments as $key => $attachement) {
					$image = $attachement;
					$extension = $image->getClientOriginalExtension();
					$name = $image->getClientOriginalName();
					$file_name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
					$value = rand(1, 100);
					$extension = $image->getClientOriginalExtension();
					$name = $trip->id .'-' . $value . '-Other-expense-' . $file_name;
					$image->move(storage_path('app/public/trip/local-trip/attachments/'), $name);
					$attachement_file = new Attachment;
					$attachement_file->attachment_of_id = 3188;
					$attachement_file->attachment_type_id = 3200;
					$attachement_file->entity_id = $trip->id;
					$attachement_file->name = $name;
					$attachement_file->save();
				}
			}

			$employee = Employee::where('id', $trip->employee_id)->first();
			$user = User::where('entity_id', $employee->reporting_to_id)->where('user_type_id', 3121)->first();

			DB::commit();
			$notification_type = 'Trip Requested';
			if ($trip->status_id == 3023) {
				$notification_type = 'Claim Requested';
			}
			$notification = sendnotification($type = 1, $trip, $user, $trip_type = "Local Trip", $notification_type = $notification_type);

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

	public static function getFilterData($type) {

		$data = [];
		if ($type == 1) {
			$grade = Auth::user()->entity;
			$data['purpose_list'] = DB::table('grade_trip_purpose')->select('trip_purpose_id', 'entities.name', 'entities.id')->join('entities', 'entities.id', 'grade_trip_purpose.trip_purpose_id')->where('grade_trip_purpose.grade_id', $grade->grade_id)->where('entities.company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Purpose']);
			$data['trip_status_list'] = collect(Config::select('name', 'id')->where('config_type_id', 501)->where(DB::raw('LOWER(name)'), '!=', strtolower("New"))->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		} elseif ($type == 2) {
			$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
			$data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.reporting_to_id', Auth::user()->entity_id)
					->where('employees.company_id', Auth::user()->company_id)
					->orderBy('users.name')
					->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
		} elseif ($type == 4) {
			$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
			$data['trip_status_list'] = collect(Config::select('name', 'id')->whereIn('id', [3023, 3024, 3026, 3030, 3034])->orderBy('id', 'asc')->get())->prepend(['id' => '-1', 'name' => 'Select Status']);
		} else {
			$data['employee_list'] = collect(Employee::select(DB::raw('CONCAT(users.name, " / ", employees.code) as name'), 'employees.id')
					->leftJoin('users', 'users.entity_id', 'employees.id')
					->where('users.user_type_id', 3121)
					->where('employees.company_id', Auth::user()->company_id)
					->orderBy('users.name')
					->get())->prepend(['id' => '-1', 'name' => 'Select Employee Code/Name']);
			$data['purpose_list'] = collect(Entity::select('name', 'id')->where('entity_type_id', 501)->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '-1', 'name' => 'Select Purpose']);
			$data['outlet_list'] = collect(Outlet::select('name', 'id')->get())->prepend(['id' => '-1', 'name' => 'Select Outlet']);
		}

		$data['financier_status_list'] = collect(Config::select('name', 'id')->whereIn('id',  [3034, 3030,3026,3025,3031])->orderBy('id', 'asc')->get())->prepend(['id' => '', 'name' => 'Select Status']);
		
		$data['success'] = true;
		return response()->json($data);
	}

	public static function getViewData($trip_id) {
		$data = [];
		$trip = LocalTrip::with([
			'visitDetails' => function ($q) {
				$q->orderBy('local_trip_visit_details.travel_date');
			},
			'visitDetails.travelMode',
			'expense',
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
			'expenseAttachments',
			'otherExpenseAttachments',
			'google_attachments',
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

		$data['trip_claim_rejection_list'] = collect(Entity::trip_claim_rejection()->prepend(['id' => '', 'name' => 'Select Rejection Reason']));

		return response()->json($data);

	}

	public static function approveTrip($trip_id) {
		$additional_approve = Auth::user()->company->additional_approve;
		$financier_approve = Auth::user()->company->financier_approve;
		$trip = LocalTrip::find($trip_id);
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Lcoal Trip not found']]);
		}
		$trip_visit_details = LocalTripVisitDetail::where('trip_id', $trip_id)->count();
		if ($trip_visit_details > 0) {
			if ($additional_approve == '1') {
				$trip->status_id = 3036;
			} else if ($financier_approve == '1') {
				$trip->status_id = 3034;
			} else {
				$trip->status_id = 3026;
			}
			$type = 6;
		} else {
			$trip->status_id = 3028;
			$type = 2;
		}

		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['details'] = 'Trip is Approved by Manager';
		$activity['activity'] = "approve";
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);

		$notification_type = 'Trip Approved';
		$message = "Trip approved successfully!";
		if ($trip->status_id == 3034 || $trip->status_id == 3036) {
			$notification_type = 'Claim Approved';
			$message = "Claim approved successfully!";

			//Claim Approval Log
			$approval_log = ApprovalLog::saveApprovalLog(3582, $trip->id, 3607, Auth::user()->entity_id, Carbon::now());
		} else {
			//Trip Approval Log
			$approval_log = ApprovalLog::saveApprovalLog(3582, $trip->id, 3606, Auth::user()->entity_id, Carbon::now());
		}

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();

		$notification = sendnotification($type, $trip, $user, $trip_type = "Local Trip", $notification_type = $notification_type);

		return response()->json(['success' => true, 'message' => $message]);
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
			$trip->status_id = 3024;
			$type = 7;
		} else {
			$trip->status_id = 3022;
			$type = 3;
		}
		$trip->save();
		$activity['entity_id'] = $trip->id;
		$activity['entity_type'] = 'trip';
		$activity['activity'] = "reject";
		$activity['details'] = 'Trip is Rejected by Manager';
		//dd($activity);
		$activity_log = ActivityLog::saveLog($activity);

		$notification_type = 'Trip Rejected';
		$message = "Trip rejected successfully!";
		if ($trip->status_id == 3024) {
			$notification_type = 'Claim Rejected';
			$message = "Claim rejected successfully!";
		}

		$user = User::where('entity_id', $trip->employee_id)->where('user_type_id', 3121)->first();
		$notification = sendnotification($type, $trip, $user, $trip_type = "Local Trip", $notification_type = $notification_type);

		return response()->json(['success' => true, 'message' => $message]);
	}

	public static function cancelTrip($trip_id) {

		$trip = LocalTrip::where('id', $trip_id)->first();
		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}
		$trip->status_id = 3032;
		$trip->save();

		return response()->json(['success' => true, 'message' => 'Trip Cancelled successfully!']);

	}

	public static function deleteTrip($trip_id) {

		$trip = LocalTrip::where('id', $trip_id)->first();

		if (!$trip) {
			return response()->json(['success' => false, 'errors' => ['Trip not found']]);
		}

		$trip = $trip->forceDelete();

		return response()->json(['success' => true]);

	}

}
