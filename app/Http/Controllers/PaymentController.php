<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
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

            return view('payment.step2', $validated);

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

//            $validator =  Validator::make($request->all(),[
//                'radioValue' => ['required'],
//                'price' => ['required','numeric'],
//                'cardNumber' => ['required'],
//                'cardMonth' => ['required'],
//                'cardYear' => ['required'],
//                'cardCVC' => ['required'],
//                'cardPassword' => ['required']
//            ]);
//            if($validator->fails()) {
//                throw new Exception();
//            }
//            $validated = $validator->validated();
            $inputData = $request->all();

            $cardDate = date("Y-m-d H:i:s", mktime(0, 0, 0, $inputData['cardMonth'] + 1, 0, $inputData['cardYear']));
            $cardValidity = date("Y-m", strtotime($cardDate));

            DB::beginTransaction();

            $mileageData = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$mileageData) {
                throw new DatabaseException();
            }

            $information = [
                'card_validity' => Crypt::encryptString($cardValidity),
                'card_account_number' => Crypt::encryptString(implode('-', $inputData['cardNumber']))
            ];

            $paymentNo = DB::table('tr_payment_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => 'credit',
                'payment_mileage' => $inputData['price'],
                'payment_information' => json_encode($information),
                'status' => 't',
                'cancels' => json_encode(['cancel' => 0])
            ]);

            if(!$paymentNo) {
                throw new DatabaseException();
            }

            $mileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => 'payment',
                'method_no' => $paymentNo,
                'before_mileage' => $mileageData->use_mileage,
                'use_mileage' => $inputData['price'],
                'after_mileage' => $mileageData->use_mileage + $inputData['price']
            ]);

            if(!$mileageLogNo) {
                throw new DatabaseException();
            }

            $updateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                'use_mileage' => $mileageData->use_mileage + $inputData['price'],
                'real_mileage' => $mileageData->real_mileage + $inputData['price']
            ]);

            if(!$updateRow) {
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect('/');
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function send(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'radioValue' => ['required', 'alpha'],
                'price' => ['required', 'integer']
            ]);
            if($validator->fails()) {
                throw new Exception('필수 입력값이 없습니다.');
            }
            $validated = $validator->validated();

            DB::beginTransaction();

            $paymentNo = DB::table('tr_payment_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => $validated['radioValue'],
                'payment_mileage' => $validated['price'],
                'status' => 'a'
            ]);
            if(!$paymentNo) {
                throw new DatabaseException('로그 생성에 실패하였습니다.');
            }

            $url = env('app_url').'/api/pg';
            $totalData = $request->all();
            $totalData->paymentNo = $paymentNo;
            $response = Http::withHeaders([
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ])->post($url, $totalData);

            if(!$response->successful()) {
                // 실패
                $failNo = DB::table('tr_payment_log')->update([
                    'status' => 'f',
                    'cancels' => json_encode(['code' => 404, 'information' => '통신 실패'])
                ]);
                if(!$failNo) {
                    DB::rollBack();
                    throw new DatabaseException('통신 및 로그 저장 실패하였습니다.');
                }
                DB::commit();
                throw new Exception('통신 실패했습니다.');
            }

            $resultData = $response->json();



//            DB::commit();
            DB::rollBack();
            $statusCode = $response->status();
            return json_encode(['status' => 'test' , 'message' => 'test', 'code' => $statusCode, 'data' => $resultData]);
//            return json_encode(['status' => 'success' , 'message' => '충전 신청이 완료 되었습니다.', 'code' => $statusCode]);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        } catch (Exception $e) {
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        }
    }
}
