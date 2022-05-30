<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Exception;
use App\Exceptions\CustomException;

class CurlController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'radioValue' => ['required'],
                'price' => ['required'],
                'paymentNo' => ['required']
            ]);
            if($validator->fails()) {
                throw new CustomException('필수 정보가 없습니다.');
            }
            $preValidated = $validator->validated();

            if($preValidated['radioValue'] === 'credit') {
                $validator = Validator::make($request->all(), [
                    'cardNumber' => ['required', 'size:4'],
                    'cardMonth' => ['required', 'integer'],
                    'cardYear' => ['required', 'integer'],
                    'cardCVC' => ['required', 'integer'],
                    'cardPassword' => ['required', 'integer']
                ]);
                if($validator->fails()) {
                    throw new CustomException('입력 정보가 올바르지 않습니다.');
                }
                $validated = $validator->validated();

                if($validated['cardMonth'] > 12 || $validated['cardMonth'] < 1) {
                    throw new CustomException('올바른 유효 기간이 아닙니다.');
                }

                $cardDate = date(
                    "Y-m-d H:i:s", mktime(0, 0, 0,
                        $validated['cardMonth'] + 1,0, $validated['cardYear']));
                $toDate = date("Y-m-d H:i:s");
                if($toDate > $cardDate) {
                    throw new CustomException('카드 유효 기간이 지났습니다.');
                }
                if(strlen($validated['cardCVC']) !== 3) {
                    throw new CustomException('보안코드가 올바르지 않습니다.');
                }
                if(strlen($validated['cardPassword']) !== 4) {
                    throw new CustomException('카드 패스워드가 올바르지 않습니다.');
                }
                foreach ($validated['cardNumber'] as $key => $item) {
                    if(strlen($item) !== 4) {
                        throw new CustomException('카드 번호길이가 알맞지 않습니다.');
                    }
                    if (!preg_match("/^[0-9]/i", $item)) {
                        throw new CustomException('숫자만 입력해 주세요');
                    }
                    $sendData['cardNumber'][$key] = Crypt::encryptString($item);
                }
                $sendData['price'] = $preValidated['price'];
                $sendData['cardDate'] = Crypt::encryptString($cardDate);
                $sendData['cardPassword'] = Crypt::encryptString($validated['cardPassword']);

            } else if($preValidated['radioValue'] === 'phone') {

            } else if($preValidated['radioValue'] === 'voucher') {

            } else {
                throw new CustomException('통신 오류');
            }

            return response()->json([
                'status' => 'success',
                'data' => $sendData,
                'payment_no' => $preValidated['paymentNo']
            ]);


        } catch (CustomException $e) {
            return response()->json(['status' => 'fail', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            Log::error("가상의 PG사 에러 : ".$e->getMessage());
            return response()->json(['status' => 'fail', 'message' => $e->getMessage()]);
        }
    }

//    public function test(Request $request)
//    {
//        try {
//
//            $paymentNo = DB::table('tr_payment')->insertGetId([
//                'user_no' => Auth::user()->no,
//                'payment_mileage' => $request->only('price')
//            ]);
//
//            // 카드정보 확인
//            $url = env('APP_URL').'/api/pg';
//            $data = $request->all();
//            $data['paymentNo'] = $paymentNo;
//
//            $response = Http::withHeaders([
//                'Accept' => '*/*',
//                'Content-Type' => 'application/json',
//                'Access-Control-Allow-Origin' => '*',
//            ])->post($url, $data);
//
//            // 통신 확인
//            if(!$response->successful()) {
//                // 실패
//                throw new Exception('카드 정보 확인 오류');
//            }
//
//            // 결과 확인
//            $resultData = $response->json();
//            if($resultData['status'] !== 'success') {
//                throw new Exception($resultData['message']);
//            }
//
//            // 결제 요청... 및 추가 로직
//            $payResponse = Http::withHeaders([
//                'Accept' => '*/*',
//                'Content-Type' => 'application/json',
//                'Access-Control-Allow-Origin' => '*',
//            ])->post($paymentUrl, $resultData['data']);
//
//            // 요청 실패
//            if(!$payResponse->successful()) {
//                throw new Exception('결제 요청 실패');
//            }
//
//            // 결제 요청 상태값 확인
//            $paymentData = $payResponse->json();
//            if($paymentData['status' !== 'success']) {
//                throw new Exception($paymentData['message']);
//            }
//
//            // 금액 재 확인
//            if($data['price'] !== $resultData['data']['price'] || $data['price'] !== $paymentData['data']['price']) {
//                throw new Exception('데이터가 변조되었습니다');
//            }
//
//            // 마일리지 작업?
//
//
//
//        } catch (Exception $e) {
//            return json_encode(['status' => 'fail', 'message' => $e->getMessage()]);
//        }
//    }

}
