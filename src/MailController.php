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
        $status='Pending Requsation Approval';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $title='Remainder';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        
        // 8-WARNING
        $date = date('Y-m-d', strtotime('-9days'));
        $title='Warning';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        
        //10-CANCELLATION
        $date = date('Y-m-d', strtotime('-11days'));
        $title='Cancelled';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        
        $status='Claim Generation';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $title='Remainder';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
         // 8-WARNING
        $date = date('Y-m-d', strtotime('-13days'));
        $title='Warning';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        
        //10-CANCELLATION
        //$date = date('Y-m-d', strtotime('-16days'));
        //$title='Cancelled';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        //$trip_mail = Trip::pendingTripMail($date,$status,$title);
        
        $status='Pending Claim Approval';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $title='Remainder';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        // 8-WARNING
        $date = date('Y-m-d', strtotime('-9days'));
        $title='Warning';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        //10-CANCELLATION
        //$date = date('Y-m-d', strtotime('-11days'));
        //$title='Cancelled';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        //$trip_mail = Trip::pendingTripMail($date,$status,$title);

        $status='Pending Divation Claim Approval';
        //2- NORMAL
        $date = date('Y-m-d', strtotime('-3days'));
        $title='Remainder';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        
        // 5-WARNING
        $date = date('Y-m-d', strtotime('-6days'));
        $title='Warning';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        $trip_mail = Trip::pendingTripMail($date,$status,$title);
        //10-CANCELLATION
        //$date = date('Y-m-d', strtotime('-11days'));
        //$title='Cancelled';
        //$local_trip_mail = LocalTrip::pendingTripMail($date,$status,$title);
        //$trip_mail = Trip::pendingTripMail($date,$status,$title);
        return true;
    }
    public function sendAutoApproveMail() {
        $status='Pending Requsation Approval';
        
        // Remainder
        $date = date('Y-m-d', strtotime('-3days'));
        $title='Remainder';
        $trip_mail = Trip::pendingTripMail($date,$status,$title);

        //2- Auto Approve
        $date = date('Y-m-d', strtotime('-4days'));
        $title='Auto-Approve';
        $trip_mail = Trip::autoApproveTripMail($date,$status,$title);

        return true;
    }
}
