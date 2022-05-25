<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Exceptions\CustomException;

class CurlController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'paymentNo' => ['required'],
                'radioValue' => ['required'],
                'price' => ['required']
            ]);
            if($validator->fails()) {
                throw new CustomException('필수 정보가 없습니다.');
            }

            $validated = $validator->validated();
            if($validated['radioValue'] === 'credit') {
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


            } else if($validated['radioValue'] === 'phone') {

            } else if($validated['radioValue'] === 'voucher') {

            } else {

            }





        } catch (CustomException $e) {
            $url = env('APP_URL');


            Http::withHeaders([
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ])->post($url);
        } catch (Exception $e) {
            Log::error("가상의 PG사 에러 : ".$e->getMessage());
        }
    }
}
