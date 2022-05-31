<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Exception;
use App\Exceptions\DatabaseException;

class PaymentController extends Controller
{
    const STATUS_TRUE = 't';
    const STATUS_FALSE = 'f';
    const STATUS_AWAIT = 'a';

    public function method(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(),[
                'price' => ['required','integer', 'digits_between:4,7'],
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

    public function success(Request $request)
    {
        return view('payment.success', ['data' => $request->all()]);
    }


    public function send(Request $request)
    {
        try {
            /*
             * 1. DB payment 저장
             * 2. curl 검증 요청 -> paymentNo 전송(고유 거래 id 느낌으로)
             * 3. 반환 값 검증
             * 4. 결과 url 띄우기
             * 5. 성공이면 결제 승인 요청 보내기
             * 6. 반환 상태값에 따라 DB 업데이트
             */
            $reqData = $request->all();

            DB::beginTransaction();

            $paymentNo = DB::table('tr_payment')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => $reqData['radioValue'],
                'payment_mileage' => $reqData['price']
            ]);
            if(!$paymentNo) {
                throw new Exception('데이터베이스 오류');
            }

            $reqData['paymentNo'] = $paymentNo;
            $reqData['successUrl'] = env('APP_URL').'/payment/success';
            $reqData['failUrl'] = env('APP_URL').'/payment/fail';

            if($reqData['radioValue'] === 'credit') {
                $data = $this->credit(Arr::only($reqData, [
                    'cardNumber',
                    'cardMonth',
                    'cardYear',
                    'cardCVC',
                    'cardPassword',
                    'price',
                    'paymentNo',
                    'successUrl',
                    'failUrl'
                ]));
                if(!$data) {
                    throw new DatabaseException('서버 통신 오류');
                }
            } else {
                return $this->other();
            }

            DB::commit();
            return $data;

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        } catch (Exception $e) {
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        }
    }

    public function credit($data)
    {
        $url = env('APP_URL')."/api/pg/credit";
        $response = Http::withHeaders([
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
        ])->post($url, $data);

        if(!$response->successful()) {
            return false;
        }

        return json_encode(['status' => 'success', 'data' => $response]);
    }
}
