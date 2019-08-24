<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class Lob extends Model {
	protected $table = 'lobss';
	protected $fillable = [
		'id',
		'name',
	];

	public function sbus() {
		return $this->hasMany('Uitoux\EYatra\Sbu');
	}

}
