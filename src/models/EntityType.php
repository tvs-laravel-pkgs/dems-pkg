<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class EntityType extends Model {
	public $timestamps = false;
	protected $fillable = [
		'id',
		'name'
	];
}
