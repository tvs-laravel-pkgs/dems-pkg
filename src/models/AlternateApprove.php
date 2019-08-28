<?php
namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlternateApprove extends Model {
	use SoftDeletes;
	protected $table = 'alternative_approvers';

	protected $fillable = [
		'employee_id',
		'alternate_employee_id',
		'from',
		'to',
		'type',
		'created_by',
	];

}