<?php

namespace Uitoux\EYatra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportJob extends Model {
	use SoftDeletes;
	protected $table = 'import_jobs';
	protected $fillable = [
		'company_id',
		'type_id',
		'total_records',
		'processed',
		'remaining',
		'new',
		'updated',
		'error',
		'status_id',
		'src_file',
		'export_file',
		'server_status',
	];
	public function type() {
		return $this->belongsTo('App\Config', 'type_id');
	}

}

?>