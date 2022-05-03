<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Exception;
use App\Exceptions\DatabaseException;

class PaymentController extends Controller
{
    public function method(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(),[
                'price' => ['required','numeric', 'digits_between:4,7'],
                'radioValue' => ['required']
            ]);

            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            return view('mileage.step2', $validated);

        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    public function credit(Request $request)
    {
        try {
            /*
             *  추후 시간 여유 있을때 충전 로직을 ajax 로 바꾸어서
             *  컨트롤러에서는 정보만 받고 저장 할 수 있게 변경
             *  지금은 시간상 충전 들어오면 바로 충전
             */

//            DB::beginTransaction();
//
//            $mileageData = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->lockForUpdate()->first();
//            if(!$mileageData) {
//                throw new DatabaseException();
//            }


//            $infomation = [
//                'card_validity' => Crypt::encryptString($request)
//            ];

            var_dump($request);
            echo $request;
//            DB::commit();
            return $request;

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect('/');
        } catch (Exception $e) {
            return redirect()->back();
        }
    }
}
