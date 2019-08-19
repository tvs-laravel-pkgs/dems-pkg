<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model {
	protected $table = 'designations';
	protected $fillable = [
		'id',
		'name',
		'code'
	];
}
