<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model {
	public $timestamps = false;

	protected $fillable = [
		// 'id',
		'attachment_of_id',
		'attachment_type_id',
		'entity_id',
		'name',

	];

	public function attachmentType() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'attachment_type_id', 'id');
	}

}
