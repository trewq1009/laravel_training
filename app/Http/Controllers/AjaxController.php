<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class AjaxController extends Controller
{
    public function visitorsList(Request $request)
    {
        try {
            $inputData = $request->all();
            if(empty($inputData['board_num']) || empty($inputData['page'])) {
                throw new Exception('필수 정보가 없습니다.');
            }

            $listData = DB::table('tr_visitors_board')->where('parents_no', $inputData['board_num'])->where('status', 't')->paginate(10);


            return json_encode(['status' => 'success', 'data' => $listData]);

        } catch (Exception $e) {
            return json_encode(['status' => 'fail', 'message' => $e->getMessage()]);
        }
    }

    public function visitorsComment(Request $request)
    {
        try {
            $inputData = $request->all();

            if(!Auth::check()) {
                if(empty($inputData['comment_password'])) {
                    throw new Exception('비회원은 패스워드가 필수 입니다.');
                }
            }
            if(empty($inputData['parent_no']) || empty($inputData['comment'])) {
                throw new Exception('필수 데이터가 없습니다.');
            }






            return json_encode(['status'=>'success', 'data'=>$inputData]);

        } catch (Exception $e) {
            return json_encode(['status'=>'fail', 'message'=>$e->getMessage()]);
        }
    }
}
