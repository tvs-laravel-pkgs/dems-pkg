<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class MobileNotificationDetail extends Model {
	protected $table = 'mobile_notification_details';
	public $timestamps = false;

	protected $fillable = [
		'user_id',
		'title',
		'message',
	];

	public function getSendOnAttribute($date) {
		return empty($date) ? '' : date('d-m-Y H:i:s ', strtotime($date));
	}

}
