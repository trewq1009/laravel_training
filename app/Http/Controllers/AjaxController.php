<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Exceptions\DatabaseException;

class AjaxController extends Controller
{
    public function visitorsList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'board_num' => ['required', 'integer'], 'page' => ['required', 'integer']]);
            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $inputData = $validator->validated();

            $listData = DB::table('tr_visitors_board')->where('parents_no', $inputData['board_num'])
                ->where('status', 't')->orderByDesc('no')->paginate(10);

            $listData = (object)$listData;
            $listData = json_encode($listData);
            $listData = json_decode($listData, true);
            foreach ($listData['data'] as $key => $value) {
                if($value['user_type'] === 'm') {
                    $listData['data'][$key]['user_name'] = Crypt::decryptString($value['user_name']);
                }
            }

            return json_encode(['status' => 'success', 'data' => $listData]);

        } catch (Exception $e) {
            return json_encode(['status' => 'fail', 'message' => $e->getMessage()]);
        }
    }

    public function visitorsComment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'parent_no' => ['required', 'integer'],
                'comment' => ['required']
            ]);
            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }
            $inputData = $validator->validated();

            if(!Auth::check()) {
                $passwordValidator = Validator::make($request->only('comment_password'), [
                    'comment_password' => ['required', 'alpha_num']]);
                if($passwordValidator->fails()) {
                    throw new Exception($passwordValidator->errors()->first());
                }
                $inputData = array_merge($inputData, $passwordValidator->validated());

                $params = ['user_type'=>'g', 'user_name'=>'게스트', 'visitors_password'=>Hash::make($inputData['comment_password']),
                            'parents_no'=>$inputData['parent_no'], 'content'=>$inputData['comment']];
            } else {
                $userModel = Auth::user();
                $params = ['user_type'=>'m', 'user_no'=>$userModel->no, 'user_name'=>$userModel->name,
                            'parents_no'=>$inputData['parent_no'], 'content'=>$inputData['comment']];
            }

            DB::beginTransaction();

            $boardNo = DB::table('tr_visitors_board')->insertGetId($params);
            if(!$boardNo) {
                throw new DatabaseException('댓글 저장에 실패했습니다.');
            }

            // email 발송 추가 예정
//            (new MailController)->sendMail()


            DB::commit();
            return json_encode(['status'=>'success', 'message'=>'댓글 등록이 완료 되었습니다.']);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        } catch (Exception $e) {
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        }
    }

    public function visitorsDelete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['board_no' => ['required', 'integer'], 'board_type' => ['required', 'alpha']]);
            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $validated = $validator->validated();

            if($validated['board_type'] === 'g') {
                $passwordValidator = Validator::make($request->only('password'), ['password'=> ['required', 'alpha_num']]);
                if($passwordValidator->fails()) {
                    throw new Exception($passwordValidator->errors()->first());
                }
                $validated = array_merge($validated, $passwordValidator->validated());
            }

            $boardModel = DB::table('tr_visitors_board')->where('status', 't')
                ->where('no', $validated['board_no'])->lockForUpdate()->first();
            if(!$boardModel) {
                throw new Exception('해당되는 게시글이 존재하지 않습니다.');
            }
            if($boardModel->user_type === 'g') {
                if(!Hash::check($validated['password'], $boardModel->visitors_password)) {
                    throw new Exception('패스워드가 일치하지 않습니다.');
                }
            } else {
                if($boardModel->user_no !== Auth::user()->no) {
                    throw new Exception('작성자가 아닙니다.');
                }
            }

            DB::beginTransaction();

            $updateData = DB::table('tr_visitors_board')->where('status', 't')
                ->where('no', $validated['board_no'])->update(['status' => 'f']);
            if(!$updateData) {
                throw new DatabaseException('삭제에 실패했습니다.');
            }

            DB::commit();
            return json_encode(['status'=>'success', 'message'=>'댓글 삭제에 성공했습니다.']);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        } catch (Exception $e) {
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        }
    }

    public function visitorsUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'board_no' => ['required', 'integer'],
                'board_type' => ['required', 'alpha'],
                'text_data' => ['required']
            ]);

            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $validated = $validator->validated();

            if($validated['board_type'] === 'g') {
                $passwordValidator = Validator::make($request->only('password'), ['password' => ['required', 'alpha_num']]);
                if($passwordValidator->fails()) {
                    throw new Exception($passwordValidator->errors()->first());
                }
                $validated = array_merge($validated, $passwordValidator->validated());
            }

            DB::beginTransaction();

            $boardModel = DB::table('tr_visitors_board')->where('no' , $validated['board_no'])
                ->where('status', 't')->lockForUpdate()->first();
            if(!$boardModel) {
                throw new DatabaseException('게시글이 존재하지 않습니다.');
            }

            if($validated['board_type'] === 'g') {
                if(!Hash::check($validated['password'], $boardModel->visitors_password)) {
                    throw new DatabaseException('패스워드를 다시 확인 해주세요.');
                }
            } else {
                if($boardModel->user_no !== Auth::user()->no) {
                    throw new DatabaseException('등록한 회원이 아닙니다.');
                }
            }

            $updateRow = DB::table('tr_visitors_board')->where('no', $validated['board_no'])
                ->where('status', 't')->update(['content' => $validated['text_data']]);
            if(!$updateRow) {
                throw new DatabaseException('수정에 실패하였습니다.');
            }

            DB::commit();
            return json_encode(['status'=>'success', 'message' => '수정이 완료되었습니다.']);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        } catch (Exception $e) {
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        }
    }

    public function payment(Request $request)
    {
        try {
            $radioValidator = Validator::make($request->all(), [
                'radioValue' => ['required', 'alpha']
            ]);

            if($radioValidator->fails()) {
                throw new Exception('잘못된 형식의 요청입니다.');
            }
            $radioValidated = $radioValidator->validated();

            if($radioValidated['radioValue'] === 'credit') {
                $params = ['price' => ['required', 'integer'],
                            'cardNumber' => ['required', 'size:4'],
                            'cardMonth' => ['required', 'integer'],
                            'cardYear' => ['required', 'integer'],
                            'cardCVC' => ['required', 'integer'],
                            'cardPassword' => ['required', 'integer']];
            } else if($radioValidated['radioValue'] === 'phone') {
                $params = [];
            } else if($radioValidated['radioValue'] === 'voucher') {
                $params = [];
            } else {
                throw new Exception('잘못된 형식의 요청 입니다');
            }

            $validator = Validator::make($request->all(), $params);
            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }
            $validated = $validator->validated();

            if($validated['price'] < 1000) {
                throw new Exception('최소 충전 금액은 1000원 이상 입니다.');
            }
            if($validated['price'] > 9999900) {
                throw new Exception('최대 충전 금액은 9999900원 입니다.');
            }
            if($validated['cardMonth'] > 12 || $validated['cardMonth'] < 1 ) {
                throw new Exception('올바른 유효 월 이 아닙니다.');
            }
            $cardDate = date("Y-m-d H:i:s", mktime(0, 0, 0, $validated['cardMonth'] + 1, 0, $validated['cardYear']));
            $toDate = date("Y-m-d H:i:s");
            if($toDate > $cardDate) {
                throw new Exception('카드 유효기간이 지났습니다.');
            }
            $validated['cardDate'] = Crypt::encryptString($cardDate);
            unset($validated['cardMonth']);
            unset($validated['cardYear']);
            if(strlen($validated['cardCVC']) !== 3) {
                throw new Exception('보안 코드가 알맞지 않습니다.');
            }
            $validated['cardCVC'] = Crypt::encryptString($validated['cardCVC']);
            if(strlen($validated['cardPassword']) !== 4) {
                throw new Exception('카드 패스워드 길이가 알맞지 않습니다.');
            }
            $validated['cardPassword'] = Crypt::encryptString($validated['cardPassword']);
            foreach ($validated['cardNumber'] as $key => $item) {
                if(strlen($item) !== 4) {
                    throw new Exception('카드 번호길이가 알맞지 않습니다.');
                }
                if (!preg_match("/^[0-9]/i", $item)) {
                    throw new Exception('숫자만 입력해 주세요');
                }
                $validated['cardNumber'][$key] = Crypt::encryptString($item);
            }

            return json_encode([
                'status'=>'success', 'message'=>'충전에 성공하였습니다.',
                'data'=>$validated, 'method'=>$radioValidated['radioValue']
            ]);

        } catch (Exception $e) {
            return json_encode([
                'status' => 'fail', 'message'=>$e->getMessage(),
                'data'=>['error'=>0, 'price'=> $validated['price'] ?? 0],
                'method' => $radioValidated['radioValue'] ?? null]
            );
        }
    }

    public function paymentInsert(Request $request)
    {
        try {
            $requestData = $request->all();
            if($requestData['status'] === 'success') {
                $params = [
                    'user_no' => Auth::user()->no, 'method' => $requestData['method'],
                    'payment_mileage' => $requestData['data']['price'], 'payment_information' => json_encode($requestData['data'])
                ];
            } else {
                $params = [
                    'user_no' => Auth::user()->no, 'method' => $requestData['method'],
                    'payment_mileage' => $requestData['data']['price'], 'status' => 'f',
                    'cancels' => json_encode(['message' => $requestData['message'], 'data' => $requestData['data']])
                ];
            }

            DB::beginTransaction();

            $paymentLogNo = DB::table('tr_payment_log')->insertGetId($params);
            if(!$paymentLogNo) {
                throw new DatabaseException('결제 로그 등록 실패');
            }

            if($requestData['status'] !== 'success') {
                return json_encode(['status'=>'fail', 'message' => '로그저장 성공']);
                die();
            }

            $userMileageModel = DB::table('tr_mileage')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();

            $calcMileage = $userMileageModel->mileage + $requestData['data']['price'];

            $mileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => Auth::user()->no, 'method' => 'payment', 'method_no' => $paymentLogNo,
                'before_mileage' => $userMileageModel->mileage, 'use_mileage' => $requestData['data']['price'],
                'after_mileage' => $calcMileage
            ]);
            if(!$mileageLogNo) {
                throw new DatabaseException('로그 저장 실패');
            }

            $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                'mileage' => $userMileageModel->mileage + $requestData['data']['price'],
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$mileageUpdateRow) {
                throw new DatabaseException('마일리지 적용 실패');
            }

            $userDetailMileageModel = DB::table('tr_mileage_detail')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$userDetailMileageModel) {
                throw new DatabaseException('로그 불러오기 실패');
            }
            $totalMileage = $userDetailMileageModel->real_mileage + $userDetailMileageModel->event_mileage;
            if($totalMileage !== $userMileageModel->mileage) {
                throw new DatabaseException('마일리지 무결성 에러');
            }

            $userDetailUpdateRow = DB::table('tr_mileage_detail')->where('user_no', Auth::user()->no)->update([
                'real_mileage' => $userDetailMileageModel->real_mileage + $requestData['data']['price']
            ]);
            if(!$userDetailUpdateRow) {
                throw new DatabaseException('마일리지 적용 실패');
            }

            DB::commit();
            return json_encode(['status' => 'success', 'message' => '충전에 성공 하였습니다.']);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status' => 'fail', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            return json_encode(['status' => 'fail', 'message' => $e->getMessage().$e->getLine()]);
        }
    }

}
