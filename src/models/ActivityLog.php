<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {
	public function saveLog($activity) {
		$activity = new ActivityLog;
		$activity->date_time = date("Y-m-d H:i:s");
		$activity->user_id = Auth::user()->id;
		$activity->entity_id = $data->entity_id;
		$activity->entity_type_id = $data->entity_type_id;
		$activity->activity_id = $data->activity_id;
		$activity->details = $data->details;

	}
}
