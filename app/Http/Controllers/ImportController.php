<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ImportController extends Controller
{
    public function credit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cardNumber' => ['required', 'size:4'],
                'cardMonth' => ['required', 'integer'],
                'cardYear' => ['required', 'integer'],
                'cardCVC' => ['required', 'integer'],
                'cardPassword' => ['required', 'integer'],
                'price' => ['required', 'integer'],
                'paymentNo' => ['required', 'integer'],
                'successUrl' => ['required'],
                'failUrl' => ['required']
            ]);
            if($validator->fails()) {
                throw new Exception('입력 정보가 올바르지 않습니다.');
            }
            $validated = $validator->validated();

            if($validated['cardMonth'] > 12 || $validated['cardMonth'] < 1) {
                throw new Exception('올바른 유효 기간이 아닙니다.');
            }
            $cardDate = date(
                "Y-m-d H:i:s", mktime(0, 0, 0,
                $validated['cardMonth'] + 1,0, $validated['cardYear']));
            $toDate = date("Y-m-d H:i:s");
            if($toDate > $cardDate) {
                throw new Exception('카드 유효 기간이 지났습니다.');
            }
            if(strlen($validated['cardCVC']) !== 3) {
                throw new Exception('보안코드가 올바르지 않습니다.');
            }
            if(strlen($validated['cardPassword']) !== 4) {
                throw new Exception('카드 패스워드가 올바르지 않습니다.');
            }
            foreach ($validated['cardNumber'] as $key => $item) {
                if(strlen($item) !== 4) {
                    throw new Exception('카드 번호길이가 알맞지 않습니다.');
                }
                if (!preg_match("/^[0-9]/i", $item)) {
                    throw new Exception('숫자만 입력해 주세요');
                }
            }

            $url = $validated['successUrl']."?paymentNo={$validated['paymentNo']}&price={$validated['price']}";

            return json_encode([
                'status' => 'success',
                'message' => '검증완료',
                'url' => $url
            ]);
        } catch (Exception $e) {
            $url = $request->only('failUrl').'?message='.$e->getMessage();
            return json_encode([
                'status' => 'fail',
                'message' => $e->getMessage(),
                'url' => $url
            ]);
        }
    }
}
