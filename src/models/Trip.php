<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'number',
		'employee_id',
		'purpose_id',
		'description',
		'status_id',
		'advance_received',
		'claim_amount',
		'claimed_date',
		'paid_amount',
		'payment_date',
		'created_by',
	];

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function visits() {
		return $this->hasMany('Uitoux\EYatra\Visit');
	}

	public function employee() {
		return $this->belongsTo('Uitoux\EYatra\Employee');
	}

	public function purpose() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'purpose_id');
	}

	public function status() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'status_id');
	}

	public function lodgings() {
		return $this->hasMany('Uitoux\EYatra\Lodging');
	}

	public function boardings() {
		return $this->hasMany('Uitoux\EYatra\Boarding');
	}

	public function localTravels() {
		return $this->hasMany('Uitoux\EYatra\LocalTravel');
	}

	public static function create($employee, $trip_number, $faker, $trip_status_id, $admin) {
		$trip = new Trip();
		$trip->employee_id = $employee->id;
		$trip->number = 'TRP' . $trip_number++;
		$trip->purpose_id = $employee->grade->tripPurposes()->inRandomOrder()->first()->id;
		$trip->description = $faker->sentence;
		$trip->manager_id = $employee->reporting_to_id;
		$trip->status_id = $trip_status_id; //NEW
		$trip->advance_received = $faker->randomElement([0, 500, 100, 1500, 2000]);
		$trip->created_by = $admin->id;
		$trip->save();
		return $trip;

	}

}
