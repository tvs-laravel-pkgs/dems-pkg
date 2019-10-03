<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;

class Sbu extends Model {
	protected $table = 'sbus';
	protected $fillable = [
		// 'id',
		'lob_id',
		'name',
	];

	public function lob() {
		return $this->belongsTo('Uitoux\EYatra\Lob');
	}

	public static function getList($r) {
		return Sbu::select('name', 'id')->whereIn('lob_id', $r->lob_ids)->get()->keyBy('id');
	}

}
