<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'payment_of_id',
		'entity_id',
		'reference_number',
		'date',
		'amount',
		'remarks',
		'payment_mode_id',
		'created_by',
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
	public function getDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}
}
