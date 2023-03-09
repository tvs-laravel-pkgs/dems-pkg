<?php

namespace Uitoux\EYatra\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TicketNotificationMail extends Mailable {
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
		//dd($this->arr);
		$view = 'mail/ticket_booking_preview';
		$this->data['visit'] = $this->arr['visits'];

		$this->data['employee_details'] = $this->arr['employee_details'];
		$this->data['to_name'] = $this->arr['to_name'];
		$this->data['body'] = $this->arr['body'];
		//$this->data['attachments'] = $this->arr['visits_attachments'];
		if($this->arr['attachment']!=''){
			$attach =  $this->arr['attachment'];
		}else{
			$attach =  '';
		}
		$this->data['base_url'] = URL::to('/');
		$this->data['body'] = $this->arr['body'];
		if (($this->arr['to_email'] != "")) {
			if($attach!=''){
			return $this->to($this->arr['to_email'])
				->from($this->arr['from_mail'], $this->arr['from_name'])
				->subject($this->arr['subject'])
				//->body($this->arr['body'])
				->view($view)
				->with($this->data)
				->attach($attach);
			}else
			{
			return $this->to($this->arr['to_email'])
				->from($this->arr['from_mail'], $this->arr['from_name'])
				->subject($this->arr['subject'])
				//->body($this->arr['body'])
				->view($view)
				->with($this->data);
			}

		}

	}


}
