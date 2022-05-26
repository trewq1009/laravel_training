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
            $validator = Validator::make($request->all(), [
                'board_no' => ['required', 'integer'],
                'board_type' => ['required', 'alpha']
            ]);
            if($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $validated = $validator->validated();

            if($validated['board_type'] === 'g') {
                $passwordValidator = Validator::make($request->only('password'), [
                    'password'=> ['required', 'alpha_num']
                ]);
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
}
