<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class Sbu extends Model {
	protected $table = 'sbus';
	protected $fillable = [
		'id',
		'lob_id',
		'name',
	];

	public function lob() {
		return $this->belongsTo('Uitoux\EYatra\Lob');
	}

}
