<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\NotifyMail;
use Illuminate\Support\Facades\Log;

class MailController extends Controller
{
    public function index()
    {
        Mail::to('kjk1009@imicorp.co.kr')->send(new NotifyMail());
        if(Mail::failures()) {
            Log::error('메일 발송 오류');
        } else {
            Log::info('메일 발송 성공');
        }
    }
}
