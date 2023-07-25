<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Auth;
use DB;
use Uitoux\EYatra\ActivityLog;
use Illuminate\Http\Request;

class PettyCashEmployeeDetails extends Model {
	// use SoftDeletes;

	protected $table = 'petty_cash_employee_details';
	public $timestamps = false;

	protected $fillable = [
		// 'id',
		'petty_cash_id',
		'expence_type',
		'petty_cash_type',
		'date',
		'purpose_id',
		'travel_mode_id',
		'from_place',
		'to_place',
		'from_km',
		'to_km',
		'amount',
		'tax',
		'remarks',
		'details',
		'created_by',
	];

	public static function proofViewSave(Request $request) {
		try {
			$error_messages = [
				'attachment_id.required' => 'Attachment ID is required',
				'attachment_id.integer' => 'Attachment ID is invalid',
				'attachment_id.exists' => 'Attachment data is not found',
				'petty_cash_detail_id.required' => 'Petty cash detail ID is required',
				'petty_cash_detail_id.integer' => 'Petty cash detail ID is invalid',
				'petty_cash_detail_id.exists' => 'Petty cash detail is not found',
				'activity_id.required' => 'Activity ID is required',
				'activity_id.integer' => 'Activity ID is invalid',
				'activity_id.exists' => 'Activity data is not found',
				'activity.required' => 'Activity is required',
			];
			$validations = [
				'attachment_id' => 'required|integer|exists:attachments,id',
				'petty_cash_detail_id' => 'required|integer|exists:petty_cash_employee_details,id',
				'activity_id' => 'required|integer|exists:configs,id',
				'activity' => 'required',
			];
			$validator = Validator::make($request->all(), $validations, $error_messages);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$activity['entity_id'] = $request->attachment_id;
			$activity['entity_type'] = 'PCV Attachment';
			$activity['details'] = "Attachment is viewed";
			$activity['activity'] = $request->activity;
			$activity_log = ActivityLog::saveLog($activity);

			$pcv_attachment_ids = Attachment::where('attachment_of_id', 3441)
				->where('attachment_type_id', 3200)
				->where('entity_id', $request->petty_cash_detail_id)
				->pluck('id');
			$pcv_attachment_count = Attachment::where('attachment_of_id', 3441)
				->where('attachment_type_id', 3200)
				->where('entity_id', $request->petty_cash_detail_id)
				->count();
			$viewed_attachment_count = ActivityLog::where('user_id' , Auth::id())
				->whereIn('entity_id', $pcv_attachment_ids)
				->where('entity_type_id', 4039) //PCV Attachment
				->where('activity_id', $request->activity_id)
				->count();

			$proof_view_pending = false;	
			if($pcv_attachment_count != $viewed_attachment_count){
				$proof_view_pending = true;
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'proof_view_pending' => $proof_view_pending,
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'errors' => ['Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile()],
			]);
		}
	}
}