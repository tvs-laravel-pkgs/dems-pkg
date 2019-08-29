<?php

namespace Uitoux\EYatra;

	use Auth;
	use DB;
	use Entrust;
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
				$this->data['visit_agent'] = $this->arr['visit_agent'];
				$this->data['body'] = $this->arr['body'];
				if (($this->arr['to_email'] != "")) {
					return $this->to($this->arr['to_email'])
						->from($this->arr['from_mail'], $this->arr['from_name'])
						//->cc($this->arr['cc_email'])
						->subject($this->arr['subject'])
						->body($this->arr['body'])
						//->view('work_logs/preview')
						->with($this->data);
				} 
			
		}

	}
