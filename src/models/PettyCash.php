<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCash extends Model {
	use SoftDeletes;
	protected $table = 'petty_cash';

	protected $fillable = [
		'employee_id',
		'total',
		'status',
		'remarks',
		'date',
		'created_by',
	];

	public function outlet() {
		return $this->belongsTo('Uitoux\EYatra\Outlet')->withTrashed();
	}

}