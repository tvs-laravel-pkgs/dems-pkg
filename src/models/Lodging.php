<?php

namespace Uitoux\EYatra;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lodging extends Model {
	use SoftDeletes;

	protected $fillable = [
		// 'id',
		'trip_id',
		'city_id',
		// 'check_in_date',
		// 'checkout_date',
		'stayed_days',
		'stay_type_id',
		'has_multiple_tax_invoice',
		'tax_invoice_amount',
		'amount',
		'tax',
		'gstin',
		'total',
		'reference_number',
		'description',
		'lodge_name',
		'eligible_amount',
		'remarks',
		'tax_percentage',
		'cgst',
		'sgst',
		'igst',
		'round_off',
		'invoice_amount',
		'invoice_date',
		'attachment_status',
		'created_by',
		'updated_by',
		'deleted_by',
	];
	// protected $attributes = ['check_in_time', 'checkout_time'];

	protected $appends = ['check_in_time', 'checkout_time'];

	// Accessors -----------------------------------------------------------------

	public function getCheckInDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getInvoiceDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y', strtotime($date));
	}

	public function getCheckInTimeAttribute() {
		return $this->attributes['check_in_time'] = date('g:i A', strtotime($this->attributes['check_in_date']));
	}

	public function getCheckoutDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getCheckoutTimeAttribute() {
		return $this->attributes['checkout_time'] = date('g:i A', strtotime($this->attributes['checkout_date']));
	}

	public function getAttachmentStatusAttribute($val) {
		return (!empty($val) && $val == 1) ? 'Yes' : 'No';
	}

	public function getHasMultipleTaxInvoiceAttribute($val) {
		return (!empty($val) && $val == 1) ? 'Yes' : 'No';
	}

	//  Mutators -----------------------------------------------------------------

	public function setAttachmentStatusAttribute($val) {
		$this->attributes['attachment_status'] = (!empty($val) && $val == 'Yes') ? 1 : 0;
	}

	// Relationships --------------------------------------------------------------

	public function trip() {
		return $this->belongsTo('Uitoux\EYatra\Trip');
	}

	public function city() {
		return $this->belongsTo('Uitoux\EYatra\NCity', 'city_id');
	}

	public function stateType() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'stay_type_id');
	}

	public function attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3181)->where('attachment_type_id', 3200);
	}

	public function pending_attachments() {
		return $this->hasMany('Uitoux\EYatra\Attachment', 'entity_id')->where('attachment_of_id', 3181)->where('attachment_type_id', 3200)->where('view_status', 0);
	}

	public function taxInvoices() {
		return $this->hasMany('Uitoux\EYatra\LodgingTaxInvoice', 'lodging_id');
	}

	public function lodgingTaxInvoice() {
		return $this->hasOne('Uitoux\EYatra\LodgingTaxInvoice', 'lodging_id')->where('type_id', 3771);
	}

	public function drywashTaxInvoice() {
		return $this->hasOne('Uitoux\EYatra\LodgingTaxInvoice', 'lodging_id')->where('type_id', 3772);
	}

	public function boardingTaxInvoice() {
		return $this->hasOne('Uitoux\EYatra\LodgingTaxInvoice', 'lodging_id')->where('type_id', 3773);
	}

	public function othersTaxInvoice() {
		return $this->hasOne('Uitoux\EYatra\LodgingTaxInvoice', 'lodging_id')->where('type_id', 3774);
	}

	public function roundoffTaxInvoice() {
		return $this->hasOne('Uitoux\EYatra\LodgingTaxInvoice', 'lodging_id')->where('type_id', 3775);
	}

}
