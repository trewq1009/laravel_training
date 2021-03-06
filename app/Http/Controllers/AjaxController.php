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

    const STATUS_TRUE = 't';
    const STATUS_FALSE = 'f';
    const TYPE_MEMBER = 'm';
    const TYPE_GUEST = 'g';

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
                ->where('status', self::STATUS_TRUE)->orderByDesc('no')->paginate(10);

            $listData = (object)$listData;
            $listData = json_encode($listData);
            $listData = json_decode($listData, true);
            foreach ($listData['data'] as $key => $value) {
                if($value['user_type'] === self::TYPE_MEMBER) {
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

                $params = [
                    'user_type' => self::TYPE_GUEST,
                    'user_name'=>'?????????',
                    'visitors_password'=>Hash::make($inputData['comment_password']),
                    'parents_no'=>$inputData['parent_no'],
                    'content'=>$inputData['comment']];
            } else {
                $userModel = Auth::user();
                $params = [
                    'user_type'=>self::TYPE_MEMBER,
                    'user_no'=>$userModel->no,
                    'user_name'=>$userModel->name,
                    'parents_no'=>$inputData['parent_no'],
                    'content'=>$inputData['comment']];
            }

            DB::beginTransaction();

            $boardNo = DB::table('tr_visitors_board')->insertGetId($params);
            if(!$boardNo) {
                throw new DatabaseException('?????? ????????? ??????????????????.');
            }

            // email ?????? ?????? ??????
//            (new MailController)->sendMail()


            DB::commit();
            return json_encode(['status'=>'success', 'message'=>'?????? ????????? ?????? ???????????????.']);

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
            $validator = Validator::make($request->all(), [
                'board_no' => ['required', 'integer'],
                'board_type' => ['required', 'alpha']
            ]);
            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $validated = $validator->validated();

            if($validated['board_type'] === self::TYPE_GUEST) {
                $passwordValidator = Validator::make($request->only('password'), [
                    'password'=> ['required', 'alpha_num']
                ]);
                if($passwordValidator->fails()) {
                    throw new Exception($passwordValidator->errors()->first());
                }
                $validated = array_merge($validated, $passwordValidator->validated());
            }

            $boardModel = DB::table('tr_visitors_board')->where('status', self::STATUS_TRUE)
                ->where('no', $validated['board_no'])->lockForUpdate()->first();
            if(!$boardModel) {
                throw new Exception('???????????? ???????????? ???????????? ????????????.');
            }
            if($boardModel->user_type === self::TYPE_GUEST) {
                if(!Hash::check($validated['password'], $boardModel->visitors_password)) {
                    throw new Exception('??????????????? ???????????? ????????????.');
                }
            } else {
                if($boardModel->user_no !== Auth::user()->no) {
                    throw new Exception('???????????? ????????????.');
                }
            }

            DB::beginTransaction();

            $updateData = DB::table('tr_visitors_board')->where('status', self::STATUS_TRUE)
                ->where('no', $validated['board_no'])->update([
                    'status' => self::STATUS_FALSE,
                    'update_date' => date('Y-m-d H:i:s')
                ]);
            if(!$updateData) {
                throw new DatabaseException('????????? ??????????????????.');
            }

            DB::commit();
            return json_encode(['status'=>'success', 'message'=>'?????? ????????? ??????????????????.']);

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

            if($validated['board_type'] === self::TYPE_GUEST) {
                $passwordValidator = Validator::make($request->only('password'), [
                    'password' => ['required', 'alpha_num']
                ]);
                if($passwordValidator->fails()) {
                    throw new Exception($passwordValidator->errors()->first());
                }
                $validated = array_merge($validated, $passwordValidator->validated());
            }

            DB::beginTransaction();

            $boardModel = DB::table('tr_visitors_board')->where('no' , $validated['board_no'])
                ->where('status', self::STATUS_TRUE)->lockForUpdate()->first();
            if(!$boardModel) {
                throw new DatabaseException('???????????? ???????????? ????????????.');
            }

            if($validated['board_type'] === self::TYPE_GUEST) {
                if(!Hash::check($validated['password'], $boardModel->visitors_password)) {
                    throw new DatabaseException('??????????????? ?????? ?????? ????????????.');
                }
            } else {
                if($boardModel->user_no !== Auth::user()->no) {
                    throw new DatabaseException('????????? ????????? ????????????.');
                }
            }

            $updateRow = DB::table('tr_visitors_board')->where('no', $validated['board_no'])
                ->where('status', self::STATUS_TRUE)->update(['content' => $validated['text_data']]);
            if(!$updateRow) {
                throw new DatabaseException('????????? ?????????????????????.');
            }

            DB::commit();
            return json_encode(['status'=>'success', 'message' => '????????? ?????????????????????.']);

        } catch (DatabaseException $e) {
            DB::rollBack();
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        } catch (Exception $e) {
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        }
    }
}
