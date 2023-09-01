<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model {
	protected $table = 'bank_details';
	public $timestamps = false;

	protected $fillable = [
		'detail_of_id',
		'entity_id',
		'bank_name',
		'branch_name',
		'account_name',
		'account_number',
		'ifsc_code',
	];
}
