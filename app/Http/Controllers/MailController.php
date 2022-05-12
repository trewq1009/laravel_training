<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\NotifyMail;
use Illuminate\Support\Facades\Log;
use Exception;

class MailController extends Controller
{
    public function sendMail($userData, $no)
    {
        try {

            Mail::to($userData['userEmail'])->send(new NotifyMail($no));
            if(Mail::failures()) {
                throw new Exception();
            }
            Log::info('메일 발송 성공');
        } catch (Exception $e) {
            Log::error('메일 발송 오류');
            Log::error($e->getMessage());
        }
    }
}
