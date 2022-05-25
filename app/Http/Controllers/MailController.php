<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\NotifyMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Exception;
use App\Exceptions\DatabaseException;

class MailController extends Controller
{
    public function sendMail($email, $no)
    {
        try {
            Mail::to($email)->send(new NotifyMail($no));
            if(Mail::failures()) {
                throw new Exception();
            }
            Log::info('메일 발송 성공');
        } catch (Exception $e) {
            Log::error('메일 발송 오류');
            Log::error($e->getMessage());
        }
    }

    public function signMail(Request $request, $hash)
    {
        try {
            $no = Crypt::decryptString($hash);

            $userModel = DB::table('tr_account')->where('email_status', 'f')
                ->where('no', $no)->first();
            if(!$userModel) {
                throw new Exception();
            }

            DB::beginTransaction();

            $userUpdateRow = DB::table('tr_account')->where('no', $no)->update(['email_status'=>'t']);
            if(!$userUpdateRow) {
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return view('errors.error', ['message' => $e->getMessage()]);
        } catch (Exception $e) {
            return view('errors.error', ['message' => $e->getMessage()]);
        }
    }
}
