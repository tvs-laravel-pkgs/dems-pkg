<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoaCode extends Model {
	use SoftDeletes;
	protected $table = 'coa_codes';

	protected $fillable = [
		// 'id',
		'number',
		'account_description',
		'account_types',
		'normal_balance',
		'description',
		'final_statement',
		'group',
		'sub_group',
		'created_by',
		'updated_by',
		'deleted_by',
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function accountType() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'account_types')->where('entity_type_id', 513);
	}

	public function normalBalance() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'normal_balance')->where('entity_type_id', 514);
	}

	public function finalStatement() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'final_statement')->where('entity_type_id', 515);
	}

	public function coaGroup() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'group')->where('entity_type_id', 516);
	}

	public function subGroup() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'sub_group')->where('entity_type_id', 517);

	}
}
