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
    const STATUS_TRUE = 't';
    const STATUS_FALSE = 'f';
    const STATUS_AWAIT = 'a';

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

            $paymentNo = DB::table('tr_payment')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => $validated['radioValue'],
                'payment_mileage' => $validated['price'],
                'status' => self::STATUS_AWAIT
            ]);
            if(!$paymentNo) {
                throw new DatabaseException('로그 생성에 실패하였습니다.');
            }

            $url = env('APP_URL').'/api/pg';
            $totalData = $request->all();
            $totalData['paymentNo'] = $paymentNo;
            $response = Http::withHeaders([
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
            ])->post($url, $totalData);

            if(!$response->successful()) {
                // 실패
                $failNo = DB::table('tr_payment')->where('no', $paymentNo)->update([
                    'status' => self::STATUS_FALSE,
                    'cancels' => json_encode(['code' => 404, 'information' => '통신 실패']),
                    'update_date' => date('Y-m-d H:i:s')
                ]);
                if(!$failNo) {
                    DB::rollBack();
                    throw new DatabaseException('통신 및 로그 저장 실패하였습니다.');
                }
                DB::commit();
                throw new Exception('통신 실패했습니다.');
            }

            /*
             * 기본 결재 정보 검증이 끝났으면
             * 다시 cURL 을 보내 결재를 준비한다
             * 결재시 응답받을 api 주소도 함께 보낸 후
             * 그곳에서 확인 후 보낸 주소로 cURL 다시 보낸다
             * 유저측 에서 받을 api 를 만들어야 한다
             */


            $resultData = $response->json();
            if($resultData['status'] !== 'success') {
                $paymentUpdateRow = DB::table('tr_payment')->where('no', $paymentNo)->update([
                    'status' => self::STATUS_FALSE,
                    'cancels' => json_encode(['code' => 200, 'information' => $resultData['message']]),
                    'update_date' => date('Y-m-d H:i:s')
                ]);
                if(!$paymentUpdateRow) {
                    throw new DatabaseException('내역 저장에 실패하였습니다.');
                }
                DB::commit();
                return json_encode(['status' => 'fail', 'message' => $resultData['message']]);
            }
            if($resultData['payment_no'] !== $paymentNo) {
                $paymentUpdateRow = DB::table('tr_payment')->where('no', $paymentNo)->update([
                    'status' => self::STATUS_FALSE,
                    'cancels' => json_encode(['code' => 400, 'information' => 'result data error']),
                    'update_date' => date('Y-m-d H:i:s')
                ]);
                if(!$paymentUpdateRow) {
                    throw new DatabaseException('내역 저장에 실패하였습니다.');
                }
                DB::commit();
                return json_encode(['status' => 'fail', 'message' => 'PG 반환 데이터 오류']);
            }

            $paymentUpdateRow = DB::table('tr_payment')->where('no', $paymentNo)->update([
                'payment_information' => json_encode($resultData['data']),
                'status' => self::STATUS_TRUE,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$paymentUpdateRow) {
                throw new DatabaseException('데이터 저장에 실패하였습니다.');
            }

            $userDetailModel = DB::table('tr_mileage_detail')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$userDetailModel) {
                throw new DatabaseException('유저의 마일리지를 불러올 수 없습니다.');
            }
            $detailUpdateRow = DB::table('tr_mileage_detail')->where('user_no', Auth::user()->no)->update([
                'real_mileage' => $userDetailModel->real_mileage + $resultData['data']['price'],
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$detailUpdateRow) {
                throw new DatabaseException('마일리지 충전에 실패하였습니다.');
            }

            $userMileageModel = DB::table('tr_mileage')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$userMileageModel) {
                throw new DatabaseException('유저의 마일리지를 불러올 수 없습니다.');
            }
            if($userMileageModel->mileage !== $userDetailModel->real_mileage + $userDetailModel->event_mileage) {
                throw new DatabaseException('데이터 무결성 에러');
            }
            $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                'mileage' => $userMileageModel->mileage + $resultData['data']['price'],
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$mileageUpdateRow) {
                throw new DatabaseException('마일리지 충전에 실패하였습니다.');
            }

            $mileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => 'payment',
                'method_no' => $paymentNo,
                'before_mileage' => $userMileageModel->mileage,
                'use_mileage' => $resultData['data']['price'],
                'after_mileage' => $userMileageModel->mileage + $resultData['data']['price']
            ]);
            if(!$mileageLogNo) {
                throw new DatabaseException('로그 저장에 실패하였습니다.');
            }

            DB::commit();
            return json_encode(['status' => 'success' , 'message' => '충전에 성공하셨습니다']);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        } catch (Exception $e) {
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        }
    }
}
