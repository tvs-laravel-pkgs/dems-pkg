<?php

namespace Uitoux\EYatra;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgetPasswordOTPMail extends Mailable {
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
		dd($this->arr);
	}

}
