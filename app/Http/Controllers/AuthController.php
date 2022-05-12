<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\DatabaseException;
use Exception;
use phpDocumentor\Reflection\TypeResolver;

class AuthController extends Controller
{
    public function create(Request $request) {
        try {
            $validator =  Validator::make($request->all(),[
                'userId' => ['required','alpha_num', 'min:5','max:20'],
                'userName' => ['required','alpha', 'max:10'],
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
                'password' => Hash::make($validated['userPw']),
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

            // 인증 이메일 발송 구역
            (new MailController)->sendMail($validated['userEmail'], Crypt::encryptString($userNo));

            DB::commit();
            return view('auth.joinSuccess');

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

            $userModelData = DB::table('tr_account')->where('id', $userData['id'])->first();
            if($userModelData->id !== $userData['id']) {
                $validator->errors()->add('userId', '아이디를 다시 확인해 주세요.');
                throw new Exception();
            }
            if($userModelData->email_status == 'f') {
                $validator->errors()->add('userId', '이메일 인증을 완료해 주세요.');
                throw new Exception();
            }
            if($userModelData->status === 'a' || $userModelData->status === 'f') {
                $validator->errors()->add('userId', '탈퇴신청 및 탈퇴 회원 입니다.');
                throw new Exception();
            }
            // 검증 후 로그인 검증 완료면 true
            if(!Auth::attempt($userData)) {
                $validator->errors()->add('userId', '계정을 다시 확인해 주세요.');
                throw new Exception();
            }

            $request->session()->regenerate();
            return redirect()->intended();

        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/');

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function profile()
    {
        try {
            if(!Auth::check()) {
                throw new Exception();
            }

            $userData = Auth::user();

            $userMileageData = DB::table('tr_mileage')->where('user_no', $userData->no)->first();

            $data = [
                'id' => $userData->id,
                'name' => Crypt::decryptString($userData->name),
                'email' => Crypt::decryptString($userData->email),
                'using_mileage' => $userMileageData->using_mileage,
                'use_mileage' => $userMileageData->use_mileage,
                'withdrawal_mileage' => $userMileageData->real_mileage
            ];

            return view('auth.profile', $data);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function update(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(),[
                'userName' => ['required','alpha', 'min:2','max:10'],
                'userPw' => ['required','min:8','max:20'],
                'userPwC' => ['required','same:userPw','min:8','max:20'],
            ]);

            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            DB::beginTransaction();

            $updateRow = DB::table('tr_account')->where('no', Auth::user()->no)
                ->update(['name' => Crypt::encryptString($validated['userName']), 'password' => Hash::make($validated['userPw'])]);

            if(!$updateRow) {
                $validator->errors()->add('field', '회원 정보 수정에 실패했습니다.');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator);
        }
    }

    public function delete(Request $request)
    {
        try {

            DB::beginTransaction();
            $userUpdateRow = DB::table('tr_account')->where('no', Auth::user()->no)->update(['status'=>'a', 'update_date'=>date('Y-m-d H:i:s')]);
            if(!$userUpdateRow) {
                throw new DatabaseException();
            }
            DB::commit();

            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back();
        } catch (Exception $e) {
            return redirect()->back();
        }
    }
}
