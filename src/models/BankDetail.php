<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model {
	protected $table = 'bank_details';
	public $timestamps = false;

	protected $fillable = [
		'bank_name',
		'branch_name',
		'account_name',
		'account_number',
		'ifsc_code',
	];
}
