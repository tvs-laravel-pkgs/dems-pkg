<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class Config extends Model {
	protected $fillable = [
		'id',
		'config_type_id',
		'name',
	];
	public $timestamps = false;

	public function configType() {
		return $this->belongsTo('App\ConfigType', 'config_type_id');
	}

}
