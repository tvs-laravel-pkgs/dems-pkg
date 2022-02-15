<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;

use Uitoux\EYatra\{Trip,LocalTrip};
use Mail;
use Illuminate\Support\Facades\URL;

class MailController extends Controller
{
    // Send mail
    public function sendMail() {
        $date = date('Y-m-d', strtotime('-3days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date);
        $trip_mail = Trip::pendingTripMail($date);
        return true;
    }
}
