<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model {
	public static function saveLog($activity) {
		//dd($activity);
		$activities_config_type = ConfigType::where(DB::raw('name'), 'Activity Log Activities - EYatra')->first();
		$entities_config_type = ConfigType::where(DB::raw('name'), 'Activity Log Entity Types - EYatra')->first();
		$entity_type_data = Config::where('config_type_id', $entities_config_type->id)->where(DB::raw('LOWER(name)'), $data->entity_type)->first();
		$activity_data = Config::where('config_type_id', $activities_config_type->id)->where(DB::raw('LOWER(name)'), $data->activity)->first();

		$activity = new ActivityLog;
		$activity->date_time = date("Y-m-d H:i:s");
		$activity->user_id = Auth::user()->id;
		$activity->entity_id = $data->entity_id;
		$activity->entity_type_id = $entity_type_data->id;
		$activity->activity_id = $activity_data->id;
		$activity->details = $data->details;

	}
}
