<?php

namespace Uitoux\EYatra;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TripNotificationMail extends Mailable {
	use Queueable, SerializesModels;
	public $arr;
	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct($arr) {
		$this->arr = $arr;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build() {
		$view = 'mail/trip_booking_preview';
		$this->data['visits'] = $this->arr['visits'];
		$this->data['trip'] = $this->arr['trip'];
		$this->data['employee_details'] = $this->arr['employee_details'];
		$this->data['type'] = $this->arr['type'];
		$this->data['to_name'] = $this->arr['to_name'];
		//dd($this->data['visits']);
		$this->data['body'] = $this->arr['body'];
		if (($this->arr['to_email'] != "")) {
			return $this->to($this->arr['to_email'])
				->from($this->arr['from_mail'], $this->arr['from_name'])
			//->cc($this->arr['cc_email'])
				->subject($this->arr['subject'])
			//->body($this->arr['body'])
				->view($view)
				->with($this->data);
		}

	}

}
