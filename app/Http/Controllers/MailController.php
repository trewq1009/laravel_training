<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\NotifyMail;

class MailController extends Controller
{
    public function index()
    {
        Mail::to('kjk1009@imicorp.co.kr')->send(new NotifyMail());
        if(Mail::failures()) {
            echo 'fail';
        } else {
            echo 'success';
        }
    }
}
