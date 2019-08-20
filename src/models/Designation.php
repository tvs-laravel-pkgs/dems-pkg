<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Designation extends Model {
	use SoftDeletes;
	protected $table = 'designations';
	protected $fillable = [
		'id',
		'name',
		'code'
	];
}
