<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class WalletDetail extends Model {
	protected $table = 'wallet_details';
	public $timestamps = false;

	protected $fillable = [
		'entity_id',
		'type_id',
		'value',
	];

	public function type() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'type_id');
	}
}
