<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agentclaim extends Model {
	use SoftDeletes;
	protected $table = 'ey_agent_claims';

	protected $fillable = [
		'number',
		'agent_id',
		'invoice_number',
		'invoice_date',
		'invoice_amount',
		'status_id',
		'payment_id',
	];
}
