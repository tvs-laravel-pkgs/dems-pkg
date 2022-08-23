<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LodgingTaxInvoice extends Model {
	use SoftDeletes;

	protected $fillable = [
		'lodging_id',
		'type_id',
		'without_tax_amount',
		'tax_percentage',
		'cgst',
		'sgst',
		'igst',
		'total',
	];

	protected $table = 'lodging_tax_invoices';

	public $timestamps = true;

	// Relationships --------------------------------------------------------------

	public function lodging() {
		return $this->belongsTo('Uitoux\EYatra\Lodging');
	}

}
