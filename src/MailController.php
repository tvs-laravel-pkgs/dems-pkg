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
        $status='Claim Generation';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
         // 8-WARNING
        $date = date('Y-m-d', strtotime('-13days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        //10-CANCELLATION
        $date = date('Y-m-d', strtotime('-16days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        
        $status='Pending Requsation Approval';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        // 8-WARNING
        $date = date('Y-m-d', strtotime('-9days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        //10-CANCELLATION
        $date = date('Y-m-d', strtotime('-11days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);

        $status='Pending Claim Approval';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        // 8-WARNING
        $date = date('Y-m-d', strtotime('-9days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        //10-CANCELLATION
        $date = date('Y-m-d', strtotime('-11days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);

        $status='Pending Divation Claim Approval';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        // 5-WARNING
        $date = date('Y-m-d', strtotime('-6days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        //10-CANCELLATION
        $date = date('Y-m-d', strtotime('-11days'));
        $local_trip_mail = LocalTrip::pendingTripMail($date,$status);
        $trip_mail = Trip::pendingTripMail($date,$status);
        return true;
    }
}
