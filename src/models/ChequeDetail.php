<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class ChequeDetail extends Model {
	protected $table = 'cheque_details';
	public $timestamps = false;

	protected $fillable = [
		'cheque_favour',
	];
}
