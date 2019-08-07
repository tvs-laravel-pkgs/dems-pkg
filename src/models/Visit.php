<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model {

	public $timestamps = false;

	protected $fillable = [
		'id',
		'trip_id',
		'from_city_id',
		'to_city_id',
		'date',
		'travel_mode_id',
		'booking_method_id',
		'booking_status_id',
		'agent_id',
		'notes_to_agent',
		'status_id',
		'manager_verification_status_id',
	];

	protected $dates = [
		// 'date',
	];

	public function setDateAttribute($v) {
		$this->attributes['date'] = $v ? date('Y-m-d', strtotime($v)) : NULL;
	}

	public function fromCity() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'from_city_id');
	}

	public function toCity() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'to_city_id');
	}

	public function travelMode() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'travel_mode_id');
	}

	public function bookingMethod() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'booking_method_id');
	}

	public function bookingStatus() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'booking_status_id');
	}

	public function claimStatus() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'claim_status_id');
	}

	public function paymentStatus() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'payment_status_id');
	}

	public function type() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'type_id');
	}

	public function agent() {
		return $this->belongsTo('Uitoux\EYatra\Agent', 'agent_id');
	}

	public function status() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'status_id');
	}

	public function managerVerificationStatus() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'manager_verification_status_id');
	}

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
	}

	public function bookings() {
		return $this->hasMany('Uitoux\EYatra\VisitBooking');
	}

}
