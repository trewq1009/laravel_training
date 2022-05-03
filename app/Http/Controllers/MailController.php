<?php

namespace App\Http\Controllers;

use App\Models\Mail as Email;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public static function sendSignUpEmail($name, $email)
    {
        $key = env('APP_NAME');
        $data = [
            'name' => $name,
            'hashCode' => Crypt::encryptString($key)
        ];

        Mail::to($email)->send(new Email($data));
    }
}
