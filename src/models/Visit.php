<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model {

	public $timestamps = false;

	protected $fillable = [
		'trip_id',
		'from_city_id',
		'to_city_id',
		'travel_mode_id',
		'booking_method_id',
		'booking_status_id',
		'agent_id',
		'notes_to_agent',
		// 'departure_date',
		// 'arrival_date',
		'status_id',
		// 'prefered_departure_time',
		'manager_verification_status_id',
	];

	protected $dates = [
		// 'date',
	];
	// protected $attributes = ['departure_time', 'arrival_time'];

	protected $appends = ['departure_time', 'arrival_time'];

	// public function setDateAttribute($v) {
	// 	$this->attributes['date'] = $v ? date('Y-m-d', strtotime($v)) : NULL;
	// }

	public function getDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getPreferedDepartureTimeAttribute($value) {
		return empty($value) ? '' : date('h:i A', strtotime($value));
	}

	public function getDepartureDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getArrivalDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getDepartureTimeAttribute() {

		return isset($this->attributes['departure_date']) ? date('g:i A', strtotime($this->attributes['departure_date'])) : '';
	}

	public function getArrivalTimeAttribute() {
		return isset($this->attributes['arrival_date']) ? date('g:i A', strtotime($this->attributes['arrival_date'])) : '';
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

	public function selfBooking() {
		return $this->hasOne('Uitoux\EYatra\VisitBooking')->where('type_id', 3100)->latest();
	}

	public function attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3180)->where('attachment_type_id', 3200);
	}
	public function changeIndianMoneyFormat($value) {
		return IND_money_format($value);
	}
	public static function create($trip, $src_city, $dest_city, $visit1_date, $company, $booking_method_id, $booking_status_id, $trip_status_id, $manager_verification_status_id, $employee, $faker) {
		$visit = new Visit();
		$visit->trip_id = $trip->id;
		$visit->from_city_id = $src_city->id;
		$visit->to_city_id = $dest_city->id;
		$visit->departure_date = $visit1_date;
		$visit->travel_mode_id = $company->travelModes()->inRandomOrder()->first()->id;
		$visit->booking_method_id = $booking_method_id;
		$visit->booking_status_id = $booking_status_id;
		$visit->status_id = $trip_status_id;
		$visit->manager_verification_status_id = $manager_verification_status_id;
		if ($visit->booking_method_id == 3042) {
			//AGENT
			$state = $employee->outlet->address->city->state;
			$agent = $state->agents()->withPivot('travel_mode_id')->where('travel_mode_id', $visit->travel_mode_id)->first();
			$visit->agent_id = $agent->id;
			$visit->notes_to_agent = $faker->sentence;
		}
		$visit->save();
		return $visit;
	}

}
