<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class Lob extends Model {
	protected $table = 'lobs';
	protected $fillable = [
		'id',
		'name',
	];

	public function sbus() {
		return $this->hasMany('Uitoux\EYatra\Sbu');
	}

}
