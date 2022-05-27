<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Exception;
use App\Exceptions\DatabaseException;

class VisitorsController extends Controller
{

    const STATUS_TRUE = 't';
    const TYPE_MEMBER = 'm';
    const TYPE_GUEST = 'g';

    public function list()
    {
        try {
            $data = DB::table('tr_visitors_board')->where('status', self::STATUS_TRUE)
                ->where('parents_no', '0')->orderByDesc('no')->paginate(10);
            $data = (object)$data;
            $data = json_encode($data);
            $data = json_decode($data);

            foreach ($data->data as $key => $value) {
                if($value->user_type === self::TYPE_MEMBER) {
                    // 이름 복호화
                    $data->data[$key]->user_name = Crypt::decryptString($value->user_name);
                }

                // 답글 수
                $data->data[$key]->comment_count = DB::table('tr_visitors_board')
                    ->where('status', self::STATUS_TRUE)->where('parents_no', $value->no)->count();
            }


            return view('visitors.list', [
                'data' => $data,
                'auth' => Auth::check() ?? false
            ]);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function insert(Request $request)
    {
        try {
            if(Auth::check()) {
                $validator =  Validator::make($request->all(),['content' => ['required']]);
            } else {
                $validator =  Validator::make($request->all(),['content' => ['required'], 'visitorsPassword' => ['required', 'alpha_num']]);
            }

            if($validator->fails()) {
                $validator->errors()->add('field', '필수 정보를 다시 확인 해주세요.');
                throw new Exception();
            }
            $validated = $validator->validated();

            if(Auth::check()) {
                $params = [
                    'user_type' => self::TYPE_MEMBER,
                    'user_no' => Auth::user()->no,
                    'user_name' => Auth::user()->name,
                    'content' => $validated['content']];
            } else {
                $params = ['user_type' => self::TYPE_GUEST,
                    'visitors_password' => Hash::make($validated['visitorsPassword']),
                    'content' => $validated['content']];
            }

            DB::beginTransaction();

            $boardNo = DB::table('tr_visitors_board')->insertGetId($params);
            if(!$boardNo) {
                $validator->errors()->add('field', '게시글 등록에 실패 했습니다.');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/visitors');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator);
        }
    }
}
