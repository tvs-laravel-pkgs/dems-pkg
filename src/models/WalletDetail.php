<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class WalletDetail extends Model {
	protected $table = 'wallet_details';
	public $timestamps = false;

	protected $fillable = [
		'id',
		'wallet_of_id',
		'entity_id',
		'type_id',
		'value',
	];
}
