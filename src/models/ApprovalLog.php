<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model {
	protected $table = 'approval_logs';
	public $timestamps = false;
	protected $fillable = [
		'type_id',
		'entity_id',
		'approval_type_id',
		'approved_by_id',
		'approved_at',
	];

	public static function saveApprovalLog($type_id, $entity_id, $approval_type_id, $approved_by_id, $approved_at) {
		$approvalLog = new self();
		$approvalLog->type_id = $type_id;
		$approvalLog->entity_id = $entity_id;
		$approvalLog->approval_type_id = $approval_type_id;
		$approvalLog->approved_by_id = $approved_by_id;
		$approvalLog->approved_at = $approved_at;
		$approvalLog->save();

	}
}
