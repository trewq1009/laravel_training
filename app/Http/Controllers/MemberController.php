<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\DatabaseException;
use Exception;
use phpDocumentor\Reflection\TypeResolver;

class MemberController extends Controller
{
    public function create(Request $request) {
        try {
            $validator =  Validator::make($request->all(),[
                'userId' => ['required','alpha_num', 'min:5','max:20'],
                'userName' => ['required','alpha'],
                'userPw' => ['required','min:8','max:20'],
                'userPwC' => ['required','same:userPw','min:8','max:20'],
                'userEmail' => ['required','email:rfc,filter']
            ]);

            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            $dbIdData = DB::table('tr_account')->where('id', $validated['userId'])->where('status', 't')->first();
            if($dbIdData) {
                $validator->errors()->add('userId', '중복된 아이디 입니다.');
                throw new Exception();
            }

            $dbEmailData = DB::table('tr_account')->where('email', $validated['userEmail'])->where('status', 't')->first();
            if($dbEmailData) {
                $validator->errors()->add('userEmail', '중복된 이메일 입니다.');
                throw new Exception();
            }

            DB::beginTransaction();

            $userNo = DB::table('tr_account')->insertGetId([
                'id' => $validated['userId'],
                'password' => Crypt::encryptString($validated['userPw']),
                'name' => Crypt::encryptString($validated['userName']),
                'email' => Crypt::encryptString($validated['userEmail'])
            ]);
            if(!$userNo) {
                $validator->errors()->add('field', '회원가입에 실패했습니다.');
                throw new DatabaseException();
            }

            $userMileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => $userNo,
                'method' => 'join'
            ]);
            if(!$userMileageLogNo) {
                $validator->errors()->add('field', '회원 가입 로그 생성에 실패했습니다.');
                throw new DatabaseException();
            }

            $userMileage = DB::table('tr_mileage')->insertGetId([
               'user_no' => $userNo
            ]);
            if(!$userMileage) {
                $validator->errors()->add('field', '회원 마일리지 생성에 실패했습니다.');
                throw new DatabaseException();
            }

            // 이메일 인증 추가 작업 구역



            DB::commit();
            return view('member.success');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator)->withInput();
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    public function login(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(),[
                'userId' => ['required','alpha_num', 'min:5','max:20'],
                'userPw' => ['required','min:8','max:20'],
            ]);

            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();
            $userData['id'] = $validated['userId'];
            $userData['password'] = $validated['userPw'];
            if (Auth::attempt($userData)) {
                $request->session()->regenerate();

                return redirect()->intended();
            }
            return redirect()->back()->withErrors([
                'userId' => '아이디를 다시 확인해 주세요.',
                'userPw' => '정보를 다시 확인 해 주세요.'
            ])->withInput();

        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    public function logout(Request $request)
    {
        try {





        } catch (Exception $e) {
            return redirect()->back();
        }
    }
}
